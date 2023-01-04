<template>
  <AppLayout
    title="Budget Settings"
    @back="$inertia.visit(route('finance'))"
    :show-back-button="true"
  >
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center space-x-2">
            <!-- Overspent notice -->
            <AtButton
              v-if="isOverspentFilterShown"
              @click="toggleOverspent"
              class="items-center min-w-fit rounded-md space-x-2 justify-between flex group"
              :class="[overspentFilter ? 'bg-primary text-white' : 'text-primary']"
            >
              <span class="relative">
                  {{ overspentCategories.length }} Overspent categories
                  <PointAlert v-if="!overspentFilter" />

                </span>
                <div class="text-white text-sm rounded-full group-hover:bg-white/20 p-0.5">
                    <IconClose />
                </div>
            </AtButton>
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
        :class="[cardShadow, !isOverspentFilterShown && 'rounded-b-md']"
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

      <div class="mx-auto mt-8 rounded-lg text-body bg-base max-w-7xl">

        <BudgetDetailForm
        class="mt-5 mr-4"
        v-if="selectedBudget"
        full
        :category="selectedBudget"
        :item="selectedBudget.budget"
        @saved="onBudgetItemSaved"
        @deleted="deleteBudget"
        @cancel="setSelectedBudget()"
        @close="setSelectedBudget()"
      />
      <div v-else class="w-full mt-4">

        <QuickBudget class="mt-4" />
      </div>

      </div>

      <template #panel class="">
        <div class="budget-right-panel ">
            <BudgetGroupForm
                v-model="categoryForm.name"
                class="rounded-md overflow-hidden"
                :class="[cardShadow]"
                @save="saveBudgetCategory()"
            />
            <Draggable
              class="w-full space-y-2 dragArea list-group overflow-auto ic-scroller"
              :list="visibleCategories"
              handle=".handle"
              @end="saveReorder(visibleCategories)"
            >
              <BudgetGroupItem
                v-for="itemGroup in visibleCategories"
                :key="itemGroup.id"
                :item="itemGroup"
                :force-expanded="overspentFilter"
                :class="[cardShadow]"
                class="bg-base-lvl-3"
              >
                <template v-slot:content="{ isExpanded, isAdding, toggleAdding }">
                  <div class="bg-base-lvl-3">
                    <div v-if="isAdding" class="pt-2">
                      <LogerInput
                        placeholder="Add subcategory"
                        v-model="state.categoryForm.name"
                        @keydown.enter.ctrl="saveBudgetCategory(itemGroup.id, toggleAdding)"
                      />
                    </div>

                    <Draggable
                      v-if="isExpanded"
                      class="py-2 space-y-2"
                      :list="itemGroup.subCategories"
                      handle=".handle"
                      @end="saveReorder(itemGroup.subCategories)"
                    >
                      <BudgetItem
                        class="bg-base-lvl-2 border-base-lvl-3"
                        v-for="item in itemGroup.subCategories"
                        :key="`${item.id}-${item.budgeted}`"
                        :item="item"
                        @edit="setSelectedBudget(item.id, itemGroup.id)"
                      />
                    </Draggable>

                </div>
            </template>
        </BudgetGroupItem>
            </Draggable>
            <ExpenseIncome :expenses="readyToAssign.activity" :income="readyToAssign.inflow" />
        </div>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { computed, reactive, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { VueDraggableNext as Draggable } from "vue-draggable-next";

import AppLayout from "@/Components/templates/AppLayout.vue";
import BudgetDetailForm from "@/Components/organisms/BudgetDetailForm.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import BudgetGroupItem from "@/Components/molecules/BudgetGroupItem.vue";
import BudgetItem from "@/Components/molecules/BudgetItem.vue";
import BudgetGroupForm from "@/Components/molecules/BudgetGroupForm.vue";
import BalanceAssign from "@/Components/organisms/BalanceAssign.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import BudgetProgress from "@/Components/molecules/BudgetProgress.vue";
import ExpenseIncome from "@/Components/widgets/ExpenseIncome.vue";
import QuickBudget from "@/Components/widgets/QuickBudget.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import IconClose from "@/Components/icons/IconClose.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { useBudget } from "@/domains/budget";
import { createBudgetCategory } from "@/domains/budget/createBudgetCategory";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import MessageBox from "@/Components/organisms/MessageBox.vue";

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

const { budgets } = toRefs(props);
const {
  readyToAssign,
  visibleCategories,
  overspentCategories,
  toggleOverspent,
  overspentFilter,
  setSelectedBudget,
  selectedBudget
} = useBudget(budgets);
const isOverspentFilterShown = computed(() => {
  return overspentFilter.value || overspentCategories.value.length > 0;
});

const state = reactive({
  isModalOpen: false,
  isAddingGroup: true,
  budgetTotal: computed(() => {
    return 0; // sumMoney(props.budgets.data.map(item => item.amount));
  }),
  categoryForm: useForm({
    account_id: null,
    parent_id: null,
    name: "",
    amount: 0,
  }),
});

const { categoryForm } = toRefs(state);

const groupById = (items) =>
  items?.reduce((items, item) => {
    items[item.id] = item;
    return items;
  }, {});

const deleteBudget = (budget) => {
  Inertia.delete(route("budgets.destroy", budget), {
    onSuccess: () => {
      Inertia.reload(["budgets"]);
    },
  });
};

const onBudgetItemSaved = () => {};

const saveBudgetCategory = (parentId, callback) => {
  if (!categoryForm.value.processing) {
    createBudgetCategory(state.categoryForm, parentId, callback);
  }
};

const saveReorder = (categories) => {
  const items = categories.map((item, index) => ({
    id: item.id,
    name: item.name,
    index,
  }));

  const savedItems = groupById(items);
  axios.patch("/api/categories/", { data: savedItems });
};

const panelSize = computed(() => {
  return !selectedBudget.value ? "large" : "small";
});

const onExport = () => {
    axios.get(route('budget.export'));
}
</script>

<style>
.budget-right-panel {
    display: grid;
    grid-template-rows: 50px calc(100vh - 420px) 150px;
    gap: 8px;
}
</style>
