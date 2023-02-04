<template>
  <AppLayout @back="router.visit('/budgets')" :show-back-button="true">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center space-x-2">
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <LogerButton variant="inverse">Import</LogerButton>
            <LogerButton variant="inverse">
              <a :href="route('budget.export')" class="block w-full" target="_blank">
                Export
              </a>
            </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate>
      <main class="py-3 space-y-4">
        <section class="rounded-md bg-white px-4 py-2">
        <ChartComparison 
          class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
          :class="[cardShadow]"
          :title="t('Spending summary')"
          ref="ComparisonRevenue"
          :data="stats"
        />
        </section>

        <section class="rounded-md bg-white px-4 py-2">
            <SectionTitle>Transactions</SectionTitle>
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

<script setup>
import LogerButton from "@/Components/atoms/LogerButton.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import BudgetDetailForm from "@/Components/organisms/BudgetDetailForm.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import TransactionSearch from "@/Components/templates/TransactionSearch.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import { useServerSearch } from "@/composables/useServerSearch";
import { AtDatePager } from "atmosphere-ui";
import { toRefs } from "vue";

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
