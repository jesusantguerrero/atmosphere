<script setup lang="ts">
import {  toRefs , ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";

import LogerInput from "@/Components/atoms/LogerInput.vue";

import BudgetGroupItem from "@/domains/budget/components/BudgetGroupItem.vue";
import BudgetItem from "@/domains/budget/components/BudgetItem.vue";
import BudgetGroupForm from "@/domains/budget/components/BudgetGroupForm.vue";

import { useBudget } from "@/domains/budget";
import { createBudgetCategory } from "@/domains/budget/createBudgetCategory";
import { ICategory } from "@/domains/transactions/models";

const props = defineProps({
  budgets: {
    type: Array,
    required: true,
  }
});

const { isSmaller } = useBreakpoints(breakpointsTailwind)
const isMobile = isSmaller('md');

const { budgets } = toRefs(props);
const {
  visibleCategories,
  filters,
  setSelectedBudget,
  assignBudget,
  moveBudget
} = useBudget(budgets);

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

const saveBudgetCategory = (parentId?: number, callback?: () => {}) => {
  if (!categoryForm.processing) {
    createBudgetCategory(categoryForm, parentId, callback);
  }
};

const saveReorder = (categories: ICategory[]) => {
  const items = categories.map((item, index) => ({
    id: item.id,
    name: item.name,
    index,
  }));

  const savedItems = groupById(items);
  axios.patch("/api/categories/", { data: savedItems });
};

const isRunningInBackground = ref(false);
const handleBudgetMovement = (budgetMovementData: any) => {
    isRunningInBackground.value = true;
    moveBudget(budgetMovementData);
    isRunningInBackground.value = false;

}
</script>

<template>
    <BudgetGroupForm
        v-model="categoryForm.name"
        class="overflow-hidden rounded-md"
        :class="[cardShadow]"
        @save="saveBudgetCategory()"
        @cancel=""
    />
    <Draggable
        class="w-full space-y-0.5 overflow-auto dragArea list-group ic-scroller"
        :list="visibleCategories"
        handle=".handle"
        @end="saveReorder(visibleCategories)"
    >
        <BudgetGroupItem
            v-for="itemGroup in visibleCategories"
            :key="itemGroup.id"
            :item="itemGroup"
            :force-expanded="filters.overspent"
            :class="[cardShadow]"
            :is-mobile="isMobile"
            class="bg-base-lvl-3"
        >
        <template v-slot:content="{ isExpanded, isAdding, toggleAdding }">
            <div class="bg-base-lvl-3">
            <div v-if="isAdding" class="px-4 pt-2" :class="{'pb-4': !isExpanded}">
                <LogerInput
                    placeholder="Add subcategory"
                    v-model="categoryForm.name"
                    :disabled="categoryForm.processing"
                    @keydown.enter="saveBudgetCategory(itemGroup.id, toggleAdding)"
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
                    class=" border-base-lvl-3 hover:bg-primary/20"
                    v-for="item in itemGroup.subCategories"
                    :class="[selectedBudget?.id == item.id ?
                        'bg-base-lvl-2 border-base-lvl-3' : 'bg-base-lvl-3' ]"
                    :key="`${item.id}-${item.budgeted}`"
                    :item="item"
                    :is-mobile="isMobile"
                    @open="router.visit(`/budgets/${item.id}`)"
                    @edit="setSelectedBudget(item.id, itemGroup.id)"
                    @assign="assignBudget({
                        category: item,
                        categoryGroup: itemGroup,
                        ...$event
                    })"
                    @move="handleBudgetMovement"
                />
            </Draggable>

        </div>
    </template>
    </BudgetGroupItem>
    </Draggable>
</template>


