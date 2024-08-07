<script setup lang="ts">
import { computed, provide, toRefs, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";
import { startOfMonth } from "date-fns";

import IconClose from "@/Components/icons/IconClose.vue";
import Modal from "@/Components/atoms/Modal.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import ExpenseIncome from "@/domains/transactions/components/ExpenseIncome.vue";

import BudgetBalanceAssign from "@/domains/budget/components/BudgetBalanceAssign.vue";
import BudgetDetailForm from "@/domains/budget/components/BudgetDetailForm.vue";

import { useBudget } from "@/domains/budget";
import { SearchFilterMode, useServerSearch } from "@/composables/useServerSearchV2";
import MessageBox from "@/Components/organisms/MessageBox.vue";
import BudgetCategories from "./Partials/BudgetCategories.vue";

import { MonthTypeFormat, formatMonth, formatMoney } from "@/utils";
import { ICategory } from "@/domains/transactions/models";

const props = defineProps({
  budgets: {
    type: Array,
    required: true,
  },
  distribution: {
    type: Object
  },
  accounts: {
    type: Array,
    default() {
      return [];
    },
  },
  accountTotal: {
    type: Number,
    default: 0
  },
  categories: {
    type: Array,
    required: true,
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
});

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay, toggleCustomFilter, setPreventWatch } = useServerSearch(
  serverSearchOptions,
  {
    manual: false,
    defaultDates: true,
  }
);

provide("pageState", pageState);

const sectionTitle = computed(() => {
  return pageState.dates.startDate ? `${formatMonth(pageState.dates.startDate, MonthTypeFormat.monthYear)}` : '--';
});

const { budgets } = toRefs(props);
const {
  readyToAssign,
  filterGroups,
  filters,
  visibleFilters,
  data: categories,
  setBudgetFilter,
  selectedBudget,
  setSelectedBudget,
} = useBudget();

const panelSize = computed(() => {
  return !selectedBudget.value ? "large" : "large";
});

const { isSmaller } = useBreakpoints(breakpointsTailwind);
const showCategoriesInMain = isSmaller("md");

//  budget filters
const budgetStatus = {
  funded: {
    label: "Funded",
  },
  underFunded: {
    label: "Not funded",
  },
};

const currentStatus = computed(() =>
    pageState?.custom?.mode
);

console.log(categories);
provide("categories", categories);

//  Budget Form
const deleteBudget = (budget: ICategory) => {
  router.delete(route("budgets.destroy", budget), {
    onSuccess: () => {
      router.reload({
        only: ["budgets"]
      });
    },
  });
};
const onBudgetItemSaved = () => {};

const goToday = () => {
  pageState.dates.startDate = startOfMonth(new Date());
  executeSearchWithDelay();
};

const toggleFilter = async (value: string) => {
    setBudgetFilter(value)
    const status = Object.keys(filters.value).find((key) => filters.value[key]) ?? "";
    toggleCustomFilter('mode', status, SearchFilterMode.Replace, false);
};

onMounted(() => {
    nextTick(() => {
        toggleFilter(currentStatus.value ?? "");
    })
})

const readyToAssignBalance = computed(() => {
    return readyToAssign.value.balance
})

const readyToAssignLeft = computed(() => {
    return readyToAssign.value.toAssign
})
</script>

<template>
  <AppLayout
    :title="sectionTitle"
    @back="router.visit(route('finance'))"
    :show-back-button="true"
  >
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center space-x-2">
            <!-- Overspent notice -->
            <AtButton
              v-if="visibleFilters.overspent"
              @click="toggleFilter('overspent')"
              class="flex items-center justify-between space-x-2 rounded-md min-w-fit group"
              :class="[filters.overspent ? 'bg-primary text-white' : 'text-primary']"
            >
              <span class="relative">
                {{ filterGroups.overSpent.length }} Overspent categories
                <PointAlert v-if="!filters.overspent" />
              </span>

              <div class="text-white text-sm rounded-full group-hover:bg-white/20 p-0.5">
                <IconClose />
              </div>
            </AtButton>
            <StatusButtons
              :modelValue="currentStatus"
              :statuses="budgetStatus"
              @change="toggleFilter"
            />

            <LogerButton variant="inverse" @click="goToday"> Today </LogerButton>


            <LogerButton variant="secondary" :href="route('budget.export')"  target="_blank" as="a">
                <IMdiExport class="mr-2" />
                Export Budget
            </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate :accounts="accounts" :panel-size="panelSize">
      <!-- Budget to assign -->
      <MessageBox
        title="This is your budget."
        content="Create new category groups and categories and organize them to suit your needs"
      />

      <BudgetBalanceAssign
        class="rounded-t-md"
        :class="[cardShadow, !visibleFilters.overspent && 'rounded-b-md']"
        :value="readyToAssignBalance"
        :category="readyToAssignLeft"
        :to-assign="readyToAssign"
      >
        <template #top>
            <AtDatePager
              v-if="pageState.dates?.startDate"
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              @change="executeSearchWithDelay(5)"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            >
              {{ formatMonth(pageState.dates.startDate, "MMMM") }}
            </AtDatePager>
        </template>
      </BudgetBalanceAssign>

      <section class="mx-auto mt-4 rounded-lg text-body bg-base max-w-7xl">
          <article class="w-full space-y-4">
            <p class="text-center">
                {{ formatMoney(accountTotal) }} {{ formatMoney(available)  }} = ({{ formatMoney(accountTotal - available)}})
            </p>

            <BudgetCategories :budgets="budgets" />
        </article>
      </section>

      <template #prepend-panel class="">
        <div class="space-y-4 ">
          <BudgetDetailForm
            v-if="selectedBudget && !showCategoriesInMain"
            full
            :category="selectedBudget"
            :item="selectedBudget.budget"
            :editable="true"
            @saved="onBudgetItemSaved"
            @deleted="deleteBudget"
            @cancel="setSelectedBudget()"
            @close="setSelectedBudget()"
          />

          <ExpenseIncome
            v-else
            :value="readyToAssign.inflow + readyToAssign.budgetedSpending"
            :footer-stats="[
              {
                label: 'Income',
                value: readyToAssign.inflow,
                class: 'text-success',
              },
              {
                label: 'Expense',
                value: readyToAssign.budgetedSpending,
                class: 'text-error',
              },
            ]"

            />
        </div>
      </template>
    </FinanceTemplate>
    <modal
      :show="selectedBudget && showCategoriesInMain"
      max-width="mobile"
      :closeable="true"
      @close="setSelectedBudget()"
    >
      <BudgetDetailForm
        full
        v-model:category="selectedBudget"
        :item="selectedBudget.budget"
        @saved="onBudgetItemSaved"
        @deleted="deleteBudget"
        @cancel="setSelectedBudget()"
        @close="setSelectedBudget()"
      />
    </modal>
  </AppLayout>
</template>

<style>
.budget-right-panel {
  display: grid;
  grid-template-rows: 50px calc(100vh - 420px) 150px;
  gap: 8px;
}
</style>
