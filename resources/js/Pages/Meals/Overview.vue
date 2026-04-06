<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import WelcomeCard from "@/Components/organisms/WelcomeCard.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ShoppingListForm from "./Partials/ShoppingListForm.vue";

import CategoryItem from "@/domains/transactions/components/CategoryItem.vue";
import MealWidget from "@/domains/meal/components/MealWidget.vue";
import MealTypeCell from "@/domains/meal/components/MealTypeCell.vue";
import MealSectionNav from "@/domains/meal/components/MealSectionNav.vue";

import { addPlan } from "./utils";

const props = defineProps<{
    meals: any;
    mostLikedMeals: any[];
    ingredients: any[];
    shoppingList: any;
    mealTypes: any[];
}>();

const getMealByType = (mealTypeId: string | number) => {
    return props.meals?.data?.find((mealPlan: any) => {
        return mealPlan.mealTypeId == mealTypeId;
    });
};

const onToggleLike = (plannedMeal: any) => {
    plannedMeal.is_liked = !Boolean(plannedMeal.is_liked);
    router.put(route("meal-planner.update", plannedMeal), plannedMeal, {
        onSuccess() {
            router.reload({ preserveScroll: true });
        },
    });
};

const onRemoved = (plannedMeal: any) => {
    router.delete(route("meal-planner.destroy", plannedMeal), {
        onSuccess() {
            router.reload({ preserveScroll: true });
        },
    });
};

const shoppingItemCount = Array.isArray(props.shoppingList?.data)
    ? props.shoppingList.data.length
    : 0;
</script>

<template>
  <AppLayout>
    <template #header>
      <MealSectionNav>
        <template #actions>
          <LogerButton variant="inverse" @click="router.visit(route('meals.create'))">
            {{ $t('New Meal') }}
          </LogerButton>
        </template>
      </MealSectionNav>
    </template>

    <div
      class="px-5 mx-auto mt-12 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
    >
      <div class="md:w-9/12">
        <!-- Today's meal plan -->
        <WelcomeCard class="mt-5">
          <SectionTitle class="mb-4">{{ $t("What am I eating today?") }}</SectionTitle>
          <article class="space-y-2">
            <div
              v-for="mealType in mealTypes"
              :key="mealType.id"
              class="w-full"
            >
              <MealTypeCell
                :planned-meal="getMealByType(mealType.id)"
                :meal-type="mealType"
                @submit="addPlan"
                @toggle-like="onToggleLike"
                @removed="onRemoved"
              />
            </div>
            <div v-if="!mealTypes || mealTypes.length === 0" class="py-4 text-center text-body-1/60">
              {{ $t('No meal types configured.') }}
            </div>
          </article>
        </WelcomeCard>

        <!-- Shopping list summary -->
        <div class="mt-4 space-y-4">
          <ShoppingListForm
            :ingredients="ingredients"
            :shopping-list="shoppingList"
          />
        </div>
      </div>

      <div class="py-6 space-y-4 md:w-3/12">
        <!-- Shopping list quick summary -->
        <div class="px-4 py-3 bg-white rounded-md shadow-sm">
          <SectionTitle type="secondary" class="text-center mb-2">
            {{ $t('Shopping List') }}
          </SectionTitle>
          <p class="text-center text-body-1">
            <span class="text-2xl font-bold text-primary">{{ shoppingItemCount }}</span>
            {{ $t('items') }}
          </p>
          <div class="mt-2 text-center">
            <button
              class="text-sm text-primary hover:underline"
              @click="router.visit(route('meal-planner.index'))"
            >
              {{ $t('View full list') }} &rarr;
            </button>
          </div>
        </div>

        <!-- Most liked meals -->
        <div class="px-2 py-2 bg-white rounded-md">
          <SectionTitle type="secondary" class="text-center">
            {{ $t('Most liked meals') }}
          </SectionTitle>
          <section class="flex flex-wrap mt-4 gap-2">
            <CategoryItem
              class="capitalize"
              wrap
              v-for="meal in mostLikedMeals"
              :key="meal.id"
              :label="meal.name"
            />
            <p v-if="!mostLikedMeals || mostLikedMeals.length === 0" class="text-sm text-body-1/60 text-center w-full py-2">
              {{ $t('No liked meals yet.') }}
            </p>
          </section>
        </div>

        <!-- Meal type quick-add cards -->
        <MealWidget :meals="meals?.data ?? []">
          <div class="mt-2">
            <div
              v-for="mealType in mealTypes"
              class="w-full"
              :key="mealType.id"
            >
              <MealTypeCell
                :planned-meal="getMealByType(mealType.id)"
                :meal-type="mealType"
                @submit="addPlan"
                @toggle-like="onToggleLike"
                @removed="onRemoved"
              />
            </div>
          </div>
        </MealWidget>
      </div>
    </div>
  </AppLayout>
</template>
