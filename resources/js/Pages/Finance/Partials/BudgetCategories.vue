<script setup lang="ts">
import { toRefs, ref, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";
import axios from "axios";

import LogerInput from "@/Components/atoms/LogerInput.vue";

import BudgetGroupItem from "@/domains/budget/components/BudgetGroupItem.vue";
import BudgetItem from "@/domains/budget/components/BudgetItem.vue";
import BudgetGroupForm from "@/domains/budget/components/BudgetGroupForm.vue";
import TotalBudgetRow from "@/domains/budget/components/TotalBudgetRow.vue";

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
  selectedBudget,
  setSelectedBudget,
  assignBudget,
  moveBudget,
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

const onSubCategoryChange = (event: any, group: any) => {
  if (event.added) {
    const movedItem = event.added.element;
    axios.patch(`/budgets/${movedItem.id}/move-to-group/${group.id}`, {
      index: event.added.newIndex,
    });
  }
  saveReorder(group.subCategories);
};

const isRunningInBackground = ref(false);
const handleBudgetMovement = (budgetMovementData: any) => {
    isRunningInBackground.value = true;
    moveBudget(budgetMovementData);
    isRunningInBackground.value = false;

}
</script>

<template>
    <!-- Empty state: new account with no budget categories yet. -->
    <div
        v-if="!visibleCategories?.length"
        class="flex flex-col items-center justify-center py-12 px-6 mt-4 rounded-md bg-base-lvl-3 border border-base"
    >
        <div class="text-4xl mb-3">📊</div>
        <h3 class="text-lg font-bold text-body">{{ $t('No categories yet') }}</h3>
        <p class="mt-1 text-sm text-body-1 text-center max-w-sm">
            {{ $t('Create your first category group above to start budgeting. Group ideas: Vivienda, Comida, Transporte.') }}
        </p>
        <!-- BudgetGroupForm surfaces here as a full-width CTA because there's
             nothing else on screen and creating the first group is THE next
             step. Once there are categories, the form moves to a compact
             pill in the header row below. -->
        <div class="w-full max-w-md mt-6">
            <BudgetGroupForm
                v-model="categoryForm.name"
                class="overflow-hidden rounded-md"
                :class="[cardShadow]"
                @save="saveBudgetCategory()"
                @cancel=""
            />
        </div>
    </div>

    <!-- "Add category group" moved to Budget.vue's toolbar as a "+"
         icon with popover so the header stays 2 rows. Subcategory
         adds still happen inline inside each group via the "+" next
         to the group name (BudgetGroupItem's toggleAdding). -->


    <Draggable
        v-if="visibleCategories?.length"
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
                    :placeholder="$t('Add subcategory')"
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
                group="budget-categories"
                @change="onSubCategoryChange($event, itemGroup)"
            >
                <BudgetItem
                    class=" border-base-lvl-3 hover:bg-primary/20"
                    v-for="item in itemGroup.subCategories"
                    :class="[selectedBudget?.id == item.id ?
                        'bg-base-lvl-2 border-base-lvl-3' : 'bg-base-lvl-3' ]"
                    :key="item.id"
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

    <!-- Sanity-check footer: sum of Assigned / Spent / Available across every
         visible group. Additive-only; nothing else depends on it, so if the
         totals ever look off it can be v-if-guarded without regressions. -->
    <TotalBudgetRow
        v-if="visibleCategories?.length"
        :budgets="visibleCategories"
        :is-mobile="isMobile"
    />
</template>


