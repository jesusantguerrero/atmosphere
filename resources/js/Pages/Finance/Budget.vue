<template>
  <AppLayout
    :title="sectionTitle"
    @back="$inertia.visit(route('finance'))"
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
              class="items-center min-w-fit rounded-md space-x-2 justify-between flex group"
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
            {{ filterGroups.underFunded.length }}
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
    <FinanceTemplate :accounts="accounts" :panel-size="panelSize">
      <!-- Budget to assign -->
      <MessageBox
        title="This is your budget."
        content="Create new category groups and categories and organize them to suit your needs"
      />
        <BalanceAssign
            class="rounded-t-md mt-5"
            :class="[cardShadow, !visibleFilters.overspent && 'rounded-b-md']"
            :value="readyToAssign.balance"
            :category="readyToAssign.toAssign"
            :to-assign="readyToAssign"
      >
        <template #activity>
          <section class="w-full flex-col justify-center  py-2 flex items-center">
            <h4 class="text-secondary font-bold">
                <MoneyPresenter :value="readyToAssign.activity" />
            </h4>
            <p class="font-bold text-body-1/80">Activity</p>
          </section>
        </template>
        <template #target>
            <BudgetProgress class="w-full text-center h-14"
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
            <BudgetDetailForm
                class="mt-5 mr-4"
                v-if="selectedBudget && showCategoriesInMain"
                full
                v-model:category="selectedBudget"
                :item="selectedBudget.budget"
                @saved="onBudgetItemSaved"
                @deleted="deleteBudget"
                @cancel="setSelectedBudget()"
                @close="setSelectedBudget()"
            />

            <article v-else class="w-full mt-4 space-y-4">
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
                    @saved="onBudgetItemSaved"
                    @deleted="deleteBudget"
                    @cancel="setSelectedBudget()"
                    @close="setSelectedBudget()"
                />
                <!-- <BudgetCategories :budgets="budgets" v-if="!showCategoriesInMain"/> -->
                <ExpenseIncome :expenses="readyToAssign.activity" :income="readyToAssign.inflow" />
            </div>
        </template>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, toRefs, unref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";

import AppLayout from "@/Components/templates/AppLayout.vue";
import BudgetDetailForm from "@/Components/organisms/BudgetDetailForm.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import BalanceAssign from "@/Components/organisms/BalanceAssign.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import BudgetProgress from "@/Components/molecules/BudgetProgress.vue";
import ExpenseIncome from "@/Components/widgets/ExpenseIncome.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import IconClose from "@/Components/icons/IconClose.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { useBudget } from "@/domains/budget";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import MessageBox from "@/Components/organisms/MessageBox.vue";
import BudgetCategories from "./Partials/BudgetCategories.vue";
import { formatMonth } from "@/utils";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";

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
const { state: pageState } = useServerSearch(serverSearchOptions);
const sectionTitle = computed(() => {
    return `Budget for ${formatMonth(pageState.dates.startDate, 'MMMM yyyy')}`
})

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
  return !selectedBudget.value ? "large" : "small";
});

const { isSmaller } = useBreakpoints(breakpointsTailwind)
const showCategoriesInMain = isSmaller('md');

//  budget filters 
const budgetStatus = {
    funded: {
        label: 'funded',
    },
    underFunded: {
        label: 'Not funded',
    },
}


const currentStatus = computed(() => Object.keys(filters.value).find(key => filters.value[key]));

//  Budget Form
const deleteBudget = (budget) => {
  Inertia.delete(route("budgets.destroy", budget), {
    onSuccess: () => {
      Inertia.reload(["budgets"]);
    },
  });
};
const onBudgetItemSaved = () => {};
</script>

<style>
.budget-right-panel {
    display: grid;
    grid-template-rows: 50px calc(100vh - 420px) 150px;
    gap: 8px;
}
</style>
