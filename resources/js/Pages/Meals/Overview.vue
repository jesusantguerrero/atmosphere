<script setup lang="ts">
import { usePage, router } from "@inertiajs/vue3";

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
    meals:  Object,
    mostLikedMeals: any[]
    ingredients: any[]
    shoppingList:  Object,
}>();

const pageProps = usePage().props;

const getMealByType = (mealTypeId) => {
  return props.meals.data.find((mealPlan) => {
    return mealPlan.mealTypeId == mealTypeId;
  });
};
</script>

<template>
  <AppLayout>
    <template #header>
      <MealSectionNav>
        <template #actions>
          <StatusButtons
            v-model="currentStatus"
            :statuses="mealStatus"
            @change="router.visit($event)"
          />
          <div>
            <LogerButton variant="inverse" @click="router.visit(route('meals.create'))">
              New Meal</LogerButton
            >
          </div>
        </template>
      </MealSectionNav>
    </template>

    <div
      class="px-5 mx-auto mt-12 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
    >
      <div class="md:w-9/12">
        <WelcomeCard class="mt-5">
          <article class="grid grid-cols-2 gap-2 md:flex md:space-x-4">
            <div
              v-for="mealType in pageProps.mealTypes"
              :key="mealType.id"
              class="flex flex-col items-center justify-center w-full h-20 font-bold text-white transition rounded-md cursor-pointer border-primary bg-primary/80"
            >
              <h4 class="capitalize">
                {{ mealType.name }}
              </h4>
              <p>{{ mealType.description }}</p>
            </div>
          </article>
        </WelcomeCard>

        <div class="mt-4 space-y-4">
            <ShoppingListForm
                :ingredients="ingredients"
                :shopping-list="shoppingList"
            />
        </div>
      </div>
      <div class="py-6 space-y-4 md:w-3/12">
        <div class="px-2 py-2 bg-white rounded-md">
          <SectionTitle type="secondary" class="text-center">
            Most liked meals
          </SectionTitle>
          <section class="flex mt-4">
            <CategoryItem
              class="capitalize"
              wrap
              v-for="mealType in mostLikedMeals"
              :label="mealType.name"
            />
          </section>
        </div>
        <MealWidget :meals="pageProps.mealTypes">
          <div class="mt-2">
            <div
              v-for="mealType in pageProps.mealTypes"
              class="w-full"
              :key="`${mealType.id}-${day}`"
            >
              <MealTypeCell
                v-model="recipe"
                :planned-meal="getMealByType(mealType.id)"
                :meal-type="mealType"
                @submit="addPlan"
                @toggle-like="onToggleLike"
                @removed="onRemoved"
              />
            </div>

            <SectionTitle type="secondary" class="text-center"> Tomorrow </SectionTitle>
            <section class="flex mt-4">
              <CategoryItem
                wrap
                v-for="mealType in pageProps.mealTypes"
                :label="`${mealType.name}`"
              />
            </section>
          </div>
        </MealWidget>
      </div>
    </div>
  </AppLayout>
</template>
