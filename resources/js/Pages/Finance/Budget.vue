<template>
  <AppLayout
    title="Budget Settings"
    @back="$inertia.visit(route('finance'))"
    :show-back-button="true"
  >
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center">
            <AtDatePager
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model="pageState.date"
              v-model:dateSpan="pageState.dateSpan"
              v-model:startDate="pageState.searchOptions.date.startDate"
              v-model:endDate="pageState.searchOptions.date.endDate"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            />
            <AtButton class="text-white rounded-md w-96 bg-primary"
              >Import Transactions</AtButton
            >
          </div>
        </template>
      </FinanceSectionNav>
    </template>
    <FinanceTemplate :accounts="accounts">
      <div class="flex justify-between px-2 py-4 mt-2 rounded-md bg-success/20">
        <span class="ml-2 font-bold text-body-1"> {{ formatMoney(readyToAssign) }} </span>
      </div>
      <div class="mx-auto mt-8 rounded-lg text-body bg-base max-w-7xl">
        <div class="flex">
          <div :class="[!selectedBudget ? 'w-full' : 'w-7/12']">
            <section class="flex">
              <AtField class="w-full">
                <LogerInput
                  v-model="categoryForm.name"
                  placeholder="New Category group"
                />
              </AtField>
              <LogerTabButton
                class="justify-center text-center min-w-max"
                @click="saveBudgetCategory()"
              >
                Add Budget Group
              </LogerTabButton>
            </section>
            <Draggable
              class="w-full mt-4 space-y-2 dragArea list-group"
              :list="budgetState"
              handle=".handle"
              @end="saveReorder(budgetState)"
            >
              <BudgetGroupItem
                v-for="itemGroup in budgetState"
                :item="itemGroup"
                class="bg-base-lvl-3"
              >
                <template v-slot:content="{ isExpanded, isAdding, toggleAdding }">
                  <div>
                    <div v-if="isAdding" class="pt-2">
                      <LogerInput
                        placeholder="Add subcategory"
                        v-model="state.categoryForm.name"
                        @keydown.enter.ctrl="
                          saveBudgetCategory(itemGroup.id, toggleAdding)
                        "
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
                        :item="item"
                      />
                    </Draggable>
                  </div>
                </template>
              </BudgetGroupItem>
            </Draggable>
          </div>
          <section
            class="py-5 text-center"
            :class="[selectedBudget ? 'w-5/12' : 'd-none']"
            v-if="selectedBudget"
          >
            <BudgetItemForm
              class="mt-5"
              full
              :category="selectedBudget"
              :item="selectedBudget.budget"
              @saved="onBudgetItemSaved"
              @deleted="deleteBudget"
              @cancel="selectedBudget = null"
            />
          </section>
        </div>
      </div>
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
import { useBudget } from "@/domains/budget";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import BudgetGroupItem from "@/Components/molecules/BudgetGroupItem.vue";
import BudgetItem from "@/Components/molecules/BudgetItem.vue";

import { useServerSearch } from "./useServerSearch";
import formatMoney from "@/utils/formatMoney";
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
const pageState = useServerSearch(serverSearchOptions);

const { categoryOptions: makeCategoryOptions } = useSelect();

const { budgets } = toRefs(props);
const { readyToAssign, budgetState } = useBudget(budgets);

const state = reactive({
  isModalOpen: false,
  isAddingGroup: true,
  expandedCategory: null,
  selectedBudget: null,
  budgetTotal: computed(() => {
    return 0; // sumMoney(props.budgets.data.map(item => item.amount));
  }),
  categoryOptions: computed(() => {
    return makeCategoryOptions(props.categories, "accounts", "categoryOptions");
  }),
  categoryForm: useForm({
    account_id: null,
    parent_id: null,
    name: "",
    amount: 0,
  }),
});

const deleteBudget = (budget) => {
  Inertia.delete(route("budgets.destroy", budget), {
    onSuccess: () => {
      Inertia.reload(["budgets"]);
    },
  });
};

const saveBudgetCategory = (parentId, callback) => {
  createBudgetCategory(state.categoryForm, parentId, callback);
};

const { categoryForm, selectedBudget } = toRefs(state);
const groupById = (items) =>
  items?.reduce((items, item) => {
    items[item.id] = item;
    return items;
  }, {});

const saveReorder = (categories) => {
  const items = categories.map((item, index) => ({
    id: item.id,
    name: item.name,
    index,
  }));

  const savedItems = groupById(items);
  axios.patch("/api/categories/", { data: savedItems });
};
</script>
