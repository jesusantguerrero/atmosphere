<script setup lang="ts">
import { ref, toRefs, computed } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";

import WatchlistCard from "@/domains/watchlist/components/WatchlistCard.vue";
import WatchlistModal from "@/domains/watchlist/components/WatchlistModal.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMonth, MonthTypeFormat } from "@/utils";

const { t } = useI18n();

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
  categories: {
    type: Array,
    default() {
      return [];
    },
  },
  accounts: {
    type: Array,
    default() {
      return [];
    },
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
});

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true,
    defaultDates: true,
});


const sectionTitle = computed(() => {
    return t("Spending watchlist for") + " " + formatMonth(pageState.dates.startDate, MonthTypeFormat.long);
})
const isModalOpen = ref(false);
const resourceToEdit = ref<Record<string, unknown> | null>(null);

const examples = [
    { name: 'Restaurants', type: 'categories', hint: 'Pick your dining categories' },
    { name: 'Subscriptions', type: 'payees', hint: 'Pick Netflix, Spotify, etc.' },
    { name: 'Delivery', type: 'payees', hint: 'Pick UberEats, PedidosYa, etc.' },
];

const openWithExample = (example: { name: string; type: string; hint: string }) => {
    resourceToEdit.value = {
        name: example.name,
        type: example.type,
        input: [],
        target: null,
    };
    isModalOpen.value = true;
};

const openBlankModal = () => {
    resourceToEdit.value = null;
    isModalOpen.value = true;
};

const handleEdit = (item: Record<string, any>) => {
    resourceToEdit.value = {
        id: item.id,
        name: item.name,
        type: item.type,
        input: item.input ?? [],
        target: item.target,
    };
    isModalOpen.value = true;
};

const handleDelete = (item: Record<string, any>) => {
    if (!window.confirm(t('Delete watchlist "{name}"?', { name: item.name }))) {
        return;
    }
    router.delete(route('watchlist.destroy', item), {
        preserveScroll: true,
    });
};

const overTargetItems = computed(() => {
    return (props.data as any[]).filter((item) => {
        const target = Number(item.target ?? 0);
        const total = Number(item.data?.month?.total ?? 0);
        return target > 0 && total > target;
    });
});

const projectedOverItems = computed(() => {
    return (props.data as any[]).filter((item) => {
        const target = Number(item.target ?? 0);
        const total = Number(item.data?.month?.total ?? 0);
        const projected = Number(item.data?.month?.projected ?? 0);
        const isCurrent = Boolean(item.data?.month?.is_current_period);
        return isCurrent && target > 0 && total <= target && projected > target;
    });
});
</script>

<template>
  <AppLayout :title="sectionTitle">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            @change="executeSearchWithDelay(5)"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          >
            {{ formatMonth(pageState.dates.startDate, "MMMM") }}
          </AtDatePager>
          <div>
            <LogerButton variant="inverse" @click="isModalOpen=!isModalOpen"> {{ $t('Add WatchList') }} </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate :title="$t('Finance')" :accounts="accounts" ref="financeTemplateRef">
      <article class="w-full">
        <header class="px-2 mt-4 mb-4">
          <h2 class="text-lg font-bold text-body">{{ $t('Spending watchlists') }}</h2>
          <p class="text-sm text-body-1 mt-1 max-w-2xl">
            {{ $t('Track categories, payees or tags with monthly targets and get alerted when you cross them.') }}
          </p>
        </header>

        <div v-if="overTargetItems.length" class="mb-4 px-4 py-3 rounded-md bg-error/10 border border-error/30 flex items-center gap-3">
          <i class="fa fa-circle-exclamation text-error" />
          <p class="text-sm text-error font-semibold flex-1">
            {{ overTargetItems.length === 1
              ? `1 watchlist crossed its target this month`
              : `${overTargetItems.length} watchlists crossed their targets this month` }}
          </p>
        </div>

        <div v-else-if="projectedOverItems.length" class="mb-4 px-4 py-3 rounded-md bg-warning/10 border border-warning/30 flex items-center gap-3">
          <i class="fa fa-triangle-exclamation text-warning" />
          <p class="text-sm text-warning font-semibold flex-1">
            {{ projectedOverItems.length === 1
              ? `1 watchlist is projected to exceed its target this month`
              : `${projectedOverItems.length} watchlists are projected to exceed their targets this month` }}
          </p>
        </div>

        <section v-if="data.length" class="grid gap-4 lg:grid-cols-3">
          <WatchlistCard
            v-for="item in data"
            :key="item.id ?? item.name"
            :item="item"
            class="cursor-pointer"
            @click="router.visit(route('watchlist.show', item))"
            @edit="handleEdit"
            @delete="handleDelete"
          />
        </section>

        <section v-else class="bg-base-lvl-3 rounded-md p-8 text-center border border-base">
          <h3 class="text-lg font-bold text-body">{{ $t('No watchlists yet') }}</h3>
          <p class="text-sm text-body-1 mt-1 max-w-md mx-auto">
            {{ $t('Pick something to track. Most people start with a category they want to spend less on.') }}
          </p>

          <div class="flex flex-wrap justify-center gap-2 mt-5">
            <button
              v-for="example in examples"
              :key="example.name"
              type="button"
              class="px-3 py-1.5 rounded-full bg-primary/10 text-primary text-sm font-semibold hover:bg-primary/20 transition"
              @click="openWithExample(example)"
            >
              {{ example.name }}
            </button>
          </div>

          <LogerButton variant="inverse" class="mt-5" @click="openBlankModal">
            {{ $t('Or start from scratch') }}
          </LogerButton>
        </section>
      </article>

      <WatchlistModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
      />
    </FinanceTemplate>
  </AppLayout>
</template>


