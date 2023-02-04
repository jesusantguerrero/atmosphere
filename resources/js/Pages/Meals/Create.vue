<template>
  <AppLayout
    :title="mealFormLabel"
    :show-back-button="true"
    @back="router.visit('/meal-planner')"
  >
    <template #header>
      <MealSectionNav>
        <template #actions>
          <div>
            <AtButton class="h-10 text-white bg-primary" rounded @click="submit()">
              Save
            </AtButton>
          </div>
        </template>
      </MealSectionNav>
    </template>

    <MealTemplate class="mx-auto" :show-meal-types="false">
      <MealForm ref="mealForm" :meal="meals" class="px-4 py-2 rounded-md bg-base-lvl-3" />
    </MealTemplate>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { AtButton } from "atmosphere-ui";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import MealForm from "@/Components/MealForm.vue";
import MealSectionNav from "@/Components/templates/MealSectionNav.vue";
import MealTemplate from "@/Components/templates/MealTemplate.vue";

const props = defineProps({
  meals: {
    type: [Object, null],
    default: null,
  },
});

const mealForm = ref(null);
const submit = () => {
  mealForm.value.submit();
};

const mealFormLabel = computed(() => {
  return props.meals ? `Meals / ${props.meals.name}` : "Create recipe";
});
</script>
