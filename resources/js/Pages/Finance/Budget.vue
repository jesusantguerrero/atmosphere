<script setup lang="ts">
import { computed, provide, toRefs } from "vue";
import { router } from "@inertiajs/vue3";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";
import { startOfMonth } from "date-fns";

import Modal from "@/Components/atoms/Modal.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ExpenseIncome from "@/Components/widgets/ExpenseIncome.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import IconClose from "@/Components/icons/IconClose.vue";

import BalanceAssign from "@/Components/organisms/BalanceAssign.vue";
import BudgetDetailForm from "@/domains/budget/components/BudgetDetailForm.vue";
import BudgetProgress from "@/domains/budget/components/BudgetProgress.vue";

import { useBudget } from "@/domains/budget";
import { useServerSearch } from "@/composables/useServerSearch";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import MessageBox from "@/Components/organisms/MessageBox.vue";
import BudgetCategories from "./Partials/BudgetCategories.vue";

import { formatMonth } from "@/utils";

const props = defineProps({
  budgets: {
    type: Array,
    required: true,
  },
  accounts: {
    type: Array,
    default() {
      return [];
    },
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
const { state: pageState, executeSearchWithDelay } = useServerSearch(
  serverSearchOptions,
  {
    manual: true,
  }
);

provide("pageState", pageState);

const sectionTitle = computed(() => {
  return `${formatMonth(pageState.dates.startDate, "MMMM yyyy")}`;
});

const { budgets } = toRefs(props);
const {
  readyToAssign,
  filterGroups,
  filters,
  visibleFilters,
  toggleFilter,
  setSelectedBudget,
  selectedBudget,
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
  Object.keys(filters.value).find((key) => filters.value[key])
);

provide("categories", budgets);

//  Budget Form
const deleteBudget = (budget) => {
  router.delete(route("budgets.destroy", budget), {
    onSuccess: () => {
      router.reload(["budgets"]);
    },
  });
};
const onBudgetItemSaved = () => {};

const goToday = () => {
  pageState.dates.startDate = startOfMonth(new Date());
  executeSearchWithDelay();
};
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

            <AtDatePager
              v-if="pageState.dates?.startDate"
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              @change="executeSearchWithDelay()"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            >
              {{ formatMonth(pageState.dates.startDate, "MMMM") }}
            </AtDatePager>
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
      <BalanceAssign
        class="mt-5 rounded-t-md"
        :class="[cardShadow, !visibleFilters.overspent && 'rounded-b-md']"
        :value="readyToAssign.balance"
        :category="readyToAssign.toAssign"
        :to-assign="readyToAssign"
      >
        <template #activity>
          <section class="flex flex-col items-center justify-center w-full py-2">
            <h4 class="font-bold text-secondary">
              <MoneyPresenter :value="readyToAssign.activity" />
            </h4>
            <p class="font-bold text-body-1/80">Activity</p>
          </section>
        </template>
        <template #target>
          <BudgetProgress
            class="w-full text-center h-14"
            :goal="readyToAssign.monthlyGoals.target"
            :current="readyToAssign.monthlyGoals.balance"
            :progress-class="['bg-secondary/10', 'bg-secondary/5']"
          >
            <section class="font-bold">
              <h4 class="text-secondary">
                <MoneyPresenter :value="readyToAssign.monthlyGoals.balance" />
              </h4>
              <p class="font-bold text-body-1/80">Monthly Goals Progress</p>
            </section>
          </BudgetProgress>
        </template>
      </BalanceAssign>

      <section class="mx-auto mt-8 rounded-lg text-body bg-base max-w-7xl">
        <article class="w-full mt-4 space-y-4">
          <section class="space-y-4">
            <BudgetCategories :budgets="budgets" />
          </section>
        </article>
      </section>

      <template #panel class="">
        <div class="mt-4 space-y-4">
          <BudgetDetailForm
            class="mt-5"
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
            :value="readyToAssign.inflow + readyToAssign.activity"
            :footer-stats="[
              {
                label: 'Income',
                value: readyToAssign.inflow,
                class: 'text-success',
              },
              {
                label: 'Expense',
                value: readyToAssign.activity,
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
