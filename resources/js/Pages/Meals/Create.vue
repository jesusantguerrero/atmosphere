<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from '@/Components/atoms/LogerButton.vue';

import MealForm from "@/domains/meal/components/MealForm.vue";
import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";
import MealTemplate from "@/domains/meal/components/MealTemplate.vue";

const props = withDefaults(defineProps<{
    meals: null|{
        name: string;
        id: number;
    }
}>(), {
    meals: null
});

const mealForm = ref();
const submit = (redirectTo?: string) => {
  mealForm.value?.submit(redirectTo);
};

const mealFormLabel = computed(() => {
  return props.meals ? `Meals / ${props.meals.name}` : "Create recipe";
});

const saveFormText = computed(() => {
    return !props.meals?.id ? 'Save' : 'Update';
})
</script>

<template>
  <AppLayout
    :title="mealFormLabel"
    :show-back-button="true"
    @back="router.visit('/meal-planner')"
  >
    <template #header>
      <MealSectionNav>
        <template #actions>
          <div class="flex space-x-2">
              <LogerButton class="h-10 text-white bg-primary" rounded @click="submit()">
                {{ saveFormText }} and keep
              </LogerButton>
            <LogerButton class="h-10 text-white bg-primary" rounded @click="submit('/meals')">
              {{ saveFormText }}
            </LogerButton>
          </div>
        </template>
      </MealSectionNav>
    </template>

    <MealTemplate class="mx-auto" :show-meal-types="false">
      <MealForm ref="mealForm" :meal="meals" class="px-4 py-2 rounded-md bg-base-lvl-3" />
    </MealTemplate>
  </AppLayout>
</template>
