<script setup lang="ts">
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";

import MealSection from "@/domains/meal/components/MealSection.vue";
import MealTemplate from "@/domains/meal/components/MealTemplate.vue";
import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import { Meal } from "@/domains/meal/models";


const props = withDefaults(defineProps<{
    meals: any[]
    shoppingList: Object,
    serverSearchOptions: Object
}>(), {
  serverSearchOptions:() => ({})
});

const recipes = computed(() => {
  return props.meals?.data ?? [];
});

const onToggleLike = (meal: Meal) => {
  meal.is_liked = !Boolean(meal.is_liked);
  router.put(
    route("meals.update", meal),
    {
      is_liked: meal.is_liked,
    },
    {
      onSuccess() {
        router.reload({
          preserveScroll: true,
        });
      },
    }
  );
};

const mealStatus = {
  all: {
    label: "All meals",
    value: "/meals",
  },
  1: {
    label: "Favorites",
    value: "/meals?filter[is_liked]=1",
  },
};

const currentStatus = ref(props.serverSearchOptions.filters?.is_liked || "all");

const goToMeal = (meal: Meal ) => router.visit(route('meals.show', { meal }))
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
          <StatusButtons
            v-model="currentStatus"
            :statuses="mealStatus"
            @change="router.visit($event)"
          />
          <div>
            <LogerButton
              class="items-center h-10 text-white bg-primary"
              rounded
              variant="secondary"
              @click="router.visit(route('meals.create'))"
            >
              New Meal
            </LogerButton>
          </div>
        </template>
      </MealSectionNav>
    </template>
    <MealTemplate class="mx-auto">
      <MealSection
        :meals="recipes"
        @click="goToMeal"
        @toggle-like="onToggleLike"
      />
    </MealTemplate>
  </AppLayout>
</template>
