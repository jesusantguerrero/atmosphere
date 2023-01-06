<template>
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
                v-model="categoryForm.name"
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
</template>

<script setup>
import { computed, reactive, toRefs } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import BudgetGroupItem from "@/Components/molecules/BudgetGroupItem.vue";
import BudgetItem from "@/Components/molecules/BudgetItem.vue";
import BudgetGroupForm from "@/Components/molecules/BudgetGroupForm.vue";

import { useBudget } from "@/domains/budget";
import { createBudgetCategory } from "@/domains/budget/createBudgetCategory";

const props = defineProps({
  budgets: {
    type: Array,
    required: true,
  }
});

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

const categoryForm = useForm({
    account_id: null,
    parent_id: null,
    name: "",
    amount: 0,
});

const groupById = (items) =>
  items?.reduce((items, item) => {
    items[item.id] = item;
    return items;
  }, {});


const saveBudgetCategory = (parentId, callback) => {
  if (!categoryForm.value.processing) {
    createBudgetCategory(categoryForm, parentId, callback);
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
