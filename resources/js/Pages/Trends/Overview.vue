<script setup lang="ts">
import { computed, ref, toRefs } from "vue";
import { router } from "@inertiajs/vue3";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";
import { formatMonth } from "@/utils";

import AppLayout from "@/Components/templates/AppLayout.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import IncomeExpenses from "@/Components/IncomeExpenses.vue";

import { useTrendOptions } from "./Partials/trendOptions";
const trendOptions = useTrendOptions();
import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import WidgetTitleCard from "@/Components/molecules/WidgetTitleCard.vue";

import ExpenseChartWidget from "@/domains/transactions/components/ExpenseChartWidget.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import AccountFilters from "@/domains/transactions/components/AccountFilters.vue";
import WidgetYearSpending from "@/Components/widgets/WidgetYearSpending.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WatchlistModal from "@/domains/watchlist/components/WatchlistModal.vue";

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  data: {
    type: Array,
    default() {
      return [];
    },
  },
  metaData: {
    type: Object
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
  section: {
    type: String
  },
  activeWatchlist: {
    type: Object,
    default: () => null,
  },
  watchlists: {
    type: Array,
    default: () => [],
  },
});

const { serverSearchOptions } = toRefs(props);
const {state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true
});

const handleSelection = (index: number) => {
    const parent: Record<string, string> = props.data[index]
    if (!props.metaData.parent_name) {
        router.visit(`/trends/categories?filter[parent_id]=${parent.id}`)
    }
}



const components = {
    groups: ExpenseChartWidget,
    categories: ExpenseChartWidget,
    netWorth: ChartNetWorth,
    incomeExpenses: IncomeExpenses,
    incomeExpensesGraph:  ChartNetWorth,
    spendingYear: WidgetYearSpending,
    assignedYear: WidgetYearSpending,
}

const trendComponent = computed(() => {
    return components[props.metaData.name] || ExpenseChartWidget
})

const isCategoryTrend = computed(() => {
    return ['group', 'categories', 'payee'].includes(props.metaData.name)
})


const isYearReport = computed(() => {
    return ['spendingYear', 'assignedYear'].includes(props.metaData.name)
})

const cashflowEntities = {
    groups: {
        label: 'Groups',
        value: '/trends/groups'
    },
    categories: {
        label: 'Categories',
        value: '/trends/categories'
    },
    payees: {
        label: 'Payees',
        value: '/trends/payees'
    }
}

const isFilterSelected = (filterValue: string) => {
    const currentStatus = location.pathname;
    return currentStatus.includes(filterValue);
}

// WL-5: save current Trends filter state as a reusable Watchlist.
// Section drives the watchlist type; the category filter (when present) becomes its input.
const sectionToWatchlistType: Record<string, string> = {
    groups: 'groups',
    categories: 'categories',
    payees: 'payees',
};

const canSaveAsReport = computed(() => Boolean(sectionToWatchlistType[props.section]));

const isWatchlistModalOpen = ref(false);
const watchlistFormData = ref<Record<string, any> | null>(null);

const openSaveAsReport = () => {
    const filterCategories = pageState.filters?.category;
    const input = Array.isArray(filterCategories) ? filterCategories : [];
    watchlistFormData.value = {
        id: null,
        name: '',
        type: sectionToWatchlistType[props.section] ?? 'categories',
        input,
        target: null,
    };
    isWatchlistModalOpen.value = true;
};

// WL-5 sub 2-3: a watchlist's `type` maps 1:1 to a Trends section URL.
const watchlistTypeToSection: Record<string, string> = {
    categories: 'categories',
    groups: 'groups',
    payees: 'payees',
};

const watchlistsForSidebar = computed(() => {
    return (props.watchlists ?? []).filter(
        (w: any) => watchlistTypeToSection[w.type]
    );
});

const watchlistHref = (watchlist: any) => {
    const sectionName = watchlistTypeToSection[watchlist.type];
    return `/trends/${sectionName}?watchlist=${watchlist.id}`;
};

const isActiveWatchlist = (watchlist: any) => {
    return props.activeWatchlist?.id === watchlist.id;
};

const clearActiveWatchlist = () => {
    router.visit(`/trends/${props.section}`, { preserveState: false });
};
</script>

<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <TrendSectionNav :sections="trendOptions">
        <template #actions>
                <div class="flex items-center w-full gap-2">
                    <AtDatePager
                        class="flex-1 h-12 border-none bg-base-lvl-1 text-body"
                        v-model:startDate="pageState.dates.startDate"
                        v-model:endDate="pageState.dates.endDate"
                        @change="executeSearchWithDelay(500)"
                        controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                        next-mode="month">
            {{ formatMonth(pageState.dates.startDate, 'MMMM yyyy') }}
        </AtDatePager>
                    <div
                        v-if="activeWatchlist"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-semibold"
                    >
                        <i class="fa fa-bookmark" />
                        <span>{{ activeWatchlist.name }}</span>
                        <button
                            type="button"
                            class="text-primary/70 hover:text-primary"
                            @click="clearActiveWatchlist"
                            :aria-label="$t('Clear report filter')"
                        >
                            <i class="fa fa-times" />
                        </button>
                    </div>
                    <LogerButton
                        v-if="canSaveAsReport"
                        variant="inverse"
                        @click="openSaveAsReport"
                    >
                        {{ $t('Save as report') }}
                    </LogerButton>
                </div>

        </template>
      </TrendSectionNav>
    </template>

    <TrendTemplate title="Finance" ref="financeTemplateRef" :hide-panel="!isCategoryTrend">
        <component
            class="mt-5"
            v-if="isYearReport"
            :is="trendComponent"
            style="width: 100%"
            :type="section"
            :series="data"
            :data="data"
            :cols="1"
            @selected="handleSelection"
            v-bind="metaData.props"
            :title="metaData.title"
            label="name"
            value="total"
            :legend="false"
            data-item-total="total_amount"
        />
        <WidgetTitleCard
            v-else
            :title="metaData.title"
            class="mt-5"
        >
            <section class="relative flex flex-wrap items-center justify-center w-full bg-base-lvl-3 md:flex-nowrap md:space-x-8">
                <component
                    :is="trendComponent"
                    style="width: 100%"
                    :type="section"
                    :series="data"
                    :data="data"
                    :cols="1"
                    @selected="handleSelection"
                    v-bind="metaData.props"
                    :title="metaData.title"
                    label="name"
                    value="total"
                    :legend="false"
                    data-item-total="total_amount"
                />
            </section>
            <template #afterActions v-if="isCategoryTrend">
                <div class="flex overflow-hidden text-white border rounded-md bg-primary border-primary min-w-max">
                    <button
                        v-for="(item, statusName) in cashflowEntities"
                        class="px-2 py-1.5 flex items-center border border-transparent hover:bg-accent"
                        :class="{'bg-base-lvl-3 text-primary border-primary hover:text-white': isFilterSelected(statusName)}"
                        :key="statusName"
                        @click="router.visit(item.value)">
                        {{ item.label }}
                    </button>
                </div>
            </template>
      </WidgetTitleCard>
      <template #prepend-panel v-if="watchlistsForSidebar.length">
        <section class="mt-5 mr-4 px-5 pt-2 pb-4 space-y-2 text-left border-b rounded-md shadow-xl bg-base-lvl-3">
            <h4 class="font-bold"> {{ $t('My Reports') }} </h4>
            <ul class="space-y-1">
                <li v-for="w in watchlistsForSidebar" :key="w.id">
                    <a
                        :href="watchlistHref(w)"
                        class="flex items-center justify-between px-2 py-1.5 rounded-md text-sm hover:bg-base-lvl-2 transition"
                        :class="isActiveWatchlist(w) ? 'bg-primary/10 text-primary font-semibold' : 'text-body'"
                    >
                        <span class="truncate">{{ w.name }}</span>
                        <span class="text-xs text-body-1/70 ml-2 capitalize">{{ w.type }}</span>
                    </a>
                </li>
            </ul>
        </section>
      </template>
      <template #panel>
        <section class="mt-5 mr-4 px-5 pt-2 pb-4 space-y-4 text-left border-b rounded-md shadow-xl bg-base-lvl-3">
            <h4 class="font-bold"> {{ $t('Filters') }} </h4>
            <AccountFilters
                class="w-full"
                include-labels
                col
                :tag-max-count="1"
                v-model:accounts="pageState.filters.account"
                v-model:categories="pageState.filters.category"
            />
        </section>
      </template>
    </TrendTemplate>

    <WatchlistModal
        v-if="isWatchlistModalOpen"
        v-model:show="isWatchlistModalOpen"
        :form-data="watchlistFormData"
    />
  </AppLayout>
</template>
