<script setup lang="ts">
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { AtButton } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";

import MealSection from "@/domains/meal/components/MealSection.vue";
import MealTemplate from "@/domains/meal/components/MealTemplate.vue";
import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";

const props = defineProps({
  meals: {
    type: Array,
    required: true,
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
});

const recipes = computed(() => {
  return props.meals?.data ?? [];
});

const onToggleLike = (meal) => {
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
      <MealSection
        :meals="recipes"
        @click="router.visit(route('meals.edit', $event))"
        @toggle-like="onToggleLike"
      />
    </MealTemplate>
  </AppLayout>
</template>

