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
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <AtButton class="text-white rounded-md w-72 bg-primary"
              >Import Transactions</AtButton
            >
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate :accounts="accounts" :panel-size="panelSize">

      <!-- Budget to assign -->
      <BalanceAssign
        class="rounded-t-md mt-5"
        :class="{'rounded-b-md shadow-md': !overspentCategories.length}"
        :value="readyToAssign.balance"
        :category="readyToAssign"
      />

      <!-- Overspent notice -->
      <div v-if="overspentCategories.length" class="bg-primary/40 items-center rounded-b-2xl justify-between text-white px-2 py-2 flex">
        <span>
            {{ overspentCategories.length }} overspent categories
        </span>
        <AtButton class="text-primary/80 bg-white rounded-md font-bold" @click="toggleOverspent">View categories</AtButton>
      </div>

      <div class="mx-auto mt-8 rounded-lg text-body bg-base max-w-7xl">
            <BudgetGroupForm
                v-model="categoryForm.name"
                class="shadow-md rounded-md overflow-hidden"
                @save="saveBudgetCategory()"
            />

            <Draggable
              class="w-full mt-4 space-y-2 dragArea list-group"
              :list="visibleCategories"
              handle=".handle"
              @end="saveReorder(visibleCategories)"
            >
              <BudgetGroupItem
                v-for="itemGroup in visibleCategories"
                :key="itemGroup.id"
                :item="itemGroup"
                :force-expanded="overspentFilter"
                class="bg-base-lvl-3 shadow-md"
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
                        @click="selectedBudget = item"
                      />
                    </Draggable>
                  </div>
                </template>
              </BudgetGroupItem>
            </Draggable>
      </div>

      <template #panel>
        <BudgetItemForm
            class="mt-5"
            v-if="selectedBudget"
            full
            :category="selectedBudget"
            :item="selectedBudget.budget"
            @saved="onBudgetItemSaved"
            @deleted="deleteBudget"
            @cancel="selectedBudget = null"
        />
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

import AppLayout from "@/Layouts/AppLayout.vue";
import { useSelect } from "@/utils/useSelects";
import BudgetItemForm from "@/Components/molecules/BudgetItemForm.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import BudgetGroupItem from "@/Components/molecules/BudgetGroupItem.vue";
import BudgetItem from "@/Components/molecules/BudgetItem.vue";
import BudgetGroupForm from "@/Components/molecules/BudgetGroupForm.vue";
import BalanceAssign from "@/Components/atoms/BalanceAssign.vue";

import { useServerSearch } from "./useServerSearch";
import { useBudget } from "@/domains/budget";
import { createBudgetCategory } from "@/domains/budget/createBudgetCategory";

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
    default: () => ({})
  },
});

const { serverSearchOptions } = toRefs(props);
const {state: pageState }= useServerSearch(serverSearchOptions);

const { budgets } = toRefs(props);
const { readyToAssign, visibleCategories, overspentCategories, toggleOverspent, overspentFilter } = useBudget(budgets);

const state = reactive({
  isModalOpen: false,
  isAddingGroup: true,
  selectedBudget: null,
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

const { categoryForm, selectedBudget } = toRefs(state);

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
    return selectedBudget.value ? 'large' : 'small'
})
</script>
