<script setup lang="ts">
import { AtButton } from "atmosphere-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";

import MealSection from "@/domains/meal/components/MealSection.vue";
import MealTemplate from "@/domains/meal/components/MealTemplate.vue";
import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";

import { generateRandomColor } from "@/utils";
import axios from "axios";

const props = defineProps({
  products: {
    type: Array,
    required: true,
  },
});

const productData = computed(() => {
  return props.products ?? [];
});

// Ingredients are created straight through the API (there is no create page).
// This inline quick-add lets a fresh space seed its first ingredient so recipes
// and shopping lists have something to work with.
const newIngredient = ref("");
const saving = ref(false);
const addIngredient = () => {
  const name = newIngredient.value.trim();
  if (!name || saving.value) return;
  saving.value = true;
  axios.post("/api/ingredients", { name })
    .then(() => { newIngredient.value = ""; router.reload(); })
    .finally(() => { saving.value = false; });
};

const assignProductLabel = (label: Record<string, string>, product: Record<string, string>) => {
  axios.post(`/api/ingredients/${product.id}/labels`, {
    ...label,
    color: generateRandomColor(),
  }).then(() => {
    router.reload()
  });
};
</script>

<template>
  <AppLayout
    :title="$t('Ingredients')"
    :show-back-button="true"
    @back="router.visit('/meal-planner')"
  >
    <template #header>
      <MealSectionNav>
        <template #actions>
          <div class="flex items-center gap-2">
            <form class="items-center hidden gap-2 sm:flex" @submit.prevent="addIngredient">
              <input
                v-model="newIngredient"
                type="text"
                :placeholder="$t('Add ingredient…')"
                class="h-10 px-3 text-sm border rounded-full bg-base-lvl-2 border-base text-body focus:border-primary focus:outline-none"
              />
              <button
                type="submit"
                class="inline-flex items-center h-10 gap-1.5 px-3 text-sm font-semibold text-white rounded-full bg-primary disabled:opacity-50"
                :disabled="!newIngredient.trim() || saving"
              >
                <i class="fa fa-plus"></i> {{ $t('Add ingredient') }}
              </button>
            </form>
            <AtButton
              class="items-center h-10 text-white bg-primary"
              rounded
              @click="router.visit(route('meals.create'))"
            >
              {{ $t('New Meal') }}</AtButton
            >
          </div>
        </template>
      </MealSectionNav>
    </template>
    <MealTemplate class="mx-auto">
      <div v-if="!productData.length" class="flex flex-col items-center py-12 text-center">
        <div class="flex items-center justify-center w-12 h-12 mb-3 rounded-full bg-primary/10 text-primary">
          <i class="fa fa-carrot"></i>
        </div>
        <p class="text-sm font-semibold text-body">{{ $t('No ingredients yet') }}</p>
        <p class="max-w-xs mt-1 mb-4 text-xs text-body-1/60">{{ $t('Add ingredients to build recipes and shopping lists.') }}</p>
        <form class="flex items-center w-full max-w-sm gap-2" @submit.prevent="addIngredient">
          <input
            v-model="newIngredient"
            type="text"
            :placeholder="$t('Add ingredient…')"
            class="flex-1 h-10 px-4 text-sm border rounded-full bg-base-lvl-2 border-base text-body focus:border-primary focus:outline-none"
          />
          <button
            type="submit"
            class="h-10 px-4 text-sm font-semibold text-white rounded-full bg-primary disabled:opacity-50"
            :disabled="!newIngredient.trim() || saving"
          >{{ $t('Add') }}</button>
        </form>
      </div>
      <MealSection v-else :meals="productData" @tag-selected="assignProductLabel" display="list" />
    </MealTemplate>
  </AppLayout>
</template>


