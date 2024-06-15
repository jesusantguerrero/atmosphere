<script setup lang="ts">
import { AtButton } from "atmosphere-ui";
import { computed } from "vue";
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
    title="Recipes"
    :show-back-button="true"
    @back="router.visit('/meal-planner')"
  >
    <template #header>
      <MealSectionNav>
        <template #actions>
          <div>
            <AtButton
              class="items-center h-10 text-white bg-primary"
              rounded
              @click="router.visit(route('meals.create'))"
            >
              New Meal</AtButton
            >
          </div>
        </template>
      </MealSectionNav>
    </template>
    <MealTemplate class="mx-auto">
      <MealSection :meals="productData" @tag-selected="assignProductLabel" display="list" />
    </MealTemplate>
  </AppLayout>
</template>


