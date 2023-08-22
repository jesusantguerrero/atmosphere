<script setup lang="ts">
import { toRefs } from "vue";
// @ts-ignore
import {  AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";

import { useSelect } from "@/utils/useSelects";
import { useServerSearch } from "@/composables/useServerSearch";

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  planned: {
    type: Array,
    default() {
      return [];
    },
  },
  expensesByCategory: {
    type: Array,
    default() {
      return [];
    },
  },
  expensesByCategoryGroup: {
    type: Array,
    default() {
      return [];
    },
  },
  budgetTotal: {
    type: [Number, String],
    default: 0,
  },
  income: {
    type: [Number, String],
    default: 0,
  },
  savings: {
    type: [Number, String],
  },
  lastMonthIncome: {
    type: [Number, String],
    default: 0,
  },
  transactionTotal: {
    type: [Number, String],
    default: 0,
  },
  lastMonthExpenses: {
    type: [Number, String],
    default: 0,
  },
  transactions: {
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
    manual: true
});

const { categoryOptions: transformCategoryOptions } = useSelect();
transformCategoryOptions(props.categories, "accounts", "categoryOptions");
transformCategoryOptions(props.accounts, "accounts", "accountsOptions");

</script>

<template>
  <AppLayout>
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            @change="executeSearchWithDelay"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          />
          <div>
            <LogerButton  variant="inverse">
                Import Transactions
            </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      Hola
    </FinanceTemplate>
  </AppLayout>
</template>