<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from '@/Components/atoms/LogerButton.vue';

import MealView from "@/domains/meal/components/MealView.vue";
import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";
import MealTemplate from "@/domains/meal/components/MealTemplate.vue";
import { Meal } from "@/domains/meal/models";

defineProps<{
    meals: Meal
}>();
</script>

<template>
  <AppLayout
    :title="meals.name"
    :show-back-button="true"
    @back="router.visit('/meal-planner')"
  >
    <template #header>
      <MealSectionNav>
        <template #actions>
          <div class="flex space-x-2">
            <LogerButton class="h-10 text-white bg-primary" rounded @click="router.visit(`/meals/${meals.id}/edit`)">
              {{ $t('Edit') }}
            </LogerButton>
          </div>
        </template>
      </MealSectionNav>
    </template>

    <MealTemplate class="mx-auto" :show-meal-types="false">
      <MealView
        ref="mealForm"
        :meal="meals"
        class="rounded-md bg-base-lvl-3"
      />
    </MealTemplate>
  </AppLayout>
</template>
