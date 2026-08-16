<script setup lang="ts">
import { toRefs } from "vue";
// @ts-expect-error: no definitions
import { AtDatePager } from "atmosphere-ui";
import { formatMonth } from "@/utils";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";

import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";

import BudgetDetailForm from "@/domains/budget/components/BudgetDetailForm.vue";

import { useServerSearch } from "@/composables/useServerSearch";

const props = defineProps({
  resource: {
    type: Object,
  },
  transactions: {
    type: Array,
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
  }
});

const { serverSearchOptions } = toRefs(props);
const { state: pageState } = useServerSearch(serverSearchOptions);
</script>

<template>
  <AppLayout @back="router.visit('/budgets')" :show-back-button="true">
    <template #header>
      <FinanceSectionNav />
    </template>

    <FinanceTemplate>
      <main class="py-3 space-y-4">
        <section class="flex flex-wrap items-center justify-end gap-2">
          <AtDatePager
            class="h-10 border-none rounded-md bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month">
            {{ formatMonth(pageState.dates.startDate, 'MMMM yyyy') }}
        </AtDatePager>
          <LogerButton variant="inverse">{{ $t('Import') }}</LogerButton>
          <LogerButton variant="inverse">
            <a :href="route('budget.export')" class="block w-full" target="_blank">
              {{ $t('Export') }}
            </a>
          </LogerButton>
        </section>
        <section class="px-4 py-2 bg-base-lvl-3 rounded-md">
        <ChartComparison
          class="w-full mt-4 mb-10 overflow-hidden bg-base-lvl-3 rounded-lg"
          :class="[cardShadow]"
          :title="$t('Spending summary')"
          ref="ComparisonRevenue"
          :data="stats"
        />
        </section>

        <section class="px-4 py-2 bg-base-lvl-3 rounded-md">
            <SectionTitle>{{ $t('Transactions') }}</SectionTitle>
            <TransactionSearch
                :transactions="transactions"
                :server-search-options="serverSearchOptions"
            />
        </section>
      </main>

      <template #panel>
        <BudgetDetailForm
          class="mt-5 mr-4"
          full
          :category="resource"
          :item="resource.budget"
        />
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>


