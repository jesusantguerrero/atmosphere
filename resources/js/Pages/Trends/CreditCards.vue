<script setup lang="ts">
import { toRefs } from "vue";


import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import BackgroundCard from "@/Components/molecules/BackgroundCard.vue";

import { trendOptions } from "./Partials/trendOptions";
import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";


import { useServerSearch } from "@/composables/useServerSearch";
import AccountFilters from "@/domains/transactions/components/AccountFilters.vue";
import { formatMoney } from "@/utils";

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
  }
});

const { serverSearchOptions } = toRefs(props);
const {state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true
});
</script>

<template>
  <AppLayout :title="metaData.title">
    <template #header>
      <TrendSectionNav :sections="trendOptions">
        <template #actions>
                <AtDatePager
                    class="w-full h-12 border-none bg-base-lvl-1 text-body"
                    v-model:startDate="pageState.dates.startDate"
                    v-model:endDate="pageState.dates.endDate"
                    @change="executeSearchWithDelay(500)"
                    controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                    next-mode="month"
                />
                <AccountFilters
                    class="w-full bg-red-500"
                    v-model:accounts="pageState.filters.account"
                    v-model:categories="pageState.filters.category"
                />
        </template>
      </TrendSectionNav>
    </template>

    <TrendTemplate title="Finance" ref="financeTemplateRef" :hide-panel="true">
        <article>
            <header class="flex space-x-5">
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="data.lastCycleBalances.length"
                    :label="'Credits Qty'"
                    label-class="capitalize text-secondary font-base"
                />
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="formatMoney(data.creditTotal)"
                    :label="'Credit Total'"
                    label-class="capitalize text-secondary font-base"
                />
                <BackgroundCard
                    class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
                    :value="`${data.creditLineUsage}%`"
                    :label="'Credit Line Usage'"
                    label-class="capitalize text-secondary font-base"
                />
            </header>
            <ChartComparison
                class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
                :title="$t('Credit card spenses')"
                ref="ComparisonRevenue"
                :data="data.lastCycleBalances"
                :header-title-date="false"
                :data-item-label="(item: any) => {item.name}"
            />
        </article>
    </TrendTemplate>
  </AppLayout>
</template>
