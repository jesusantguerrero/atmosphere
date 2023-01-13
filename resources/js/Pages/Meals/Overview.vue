<template>
  <AppLayout>
    <template #header>
        <MealSectionNav>
            <template #actions>
                <StatusButtons
                    v-model="currentStatus"
                    :statuses="mealStatus"
                    @change="$inertia.visit($event)"
                />
               <div>
                    <LogerButton variant="inverse" @click="$inertia.visit(route('meals.create'))"> New Meal</LogerButton>
                </div>
            </template>
        </MealSectionNav>
    </template>

    <div
      class="px-5 mt-12 mx-auto space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
    >
      <div class="md:w-9/12">
        <WelcomeCard
          class="mt-5"
        >
        <article class="grid grid-cols-2 gap-2 md:flex md:space-x-4">
            <div
              v-for="mealType in pageProps.mealTypes"
              :key="mealType.id"
              class="cursor-pointer font-bold text-white border-primary transition rounded-md bg-primary/80 h-20 w-full flex flex-col items-center justify-center"
            >
              <h4 class="capitalize">
                {{ mealType.name }}
              </h4>
              <p>{{ mealType.description }}</p>
            </div>
          </article>
        </WelcomeCard>

        <div class="space-y-4 mt-4">
            <ChoppingListForm
            :ingredients="ingredients"
            >
            <template #prepend>
                <div class="rounded-md  font-bold bg-primary/40 text-body-1/80 py-2 px-4">
                    This are the things you'll need this week according to your planning
                </div>
            </template>

            </ChoppingListForm>
        </div>
      </div>
      <div class="py-6 space-y-4 md:w-3/12">
        <div class="rounded-md bg-white px-2 py-2">
            <SectionTitle type="secondary" class="text-center">
                Most liked meals
            </SectionTitle>
            <section class="flex mt-4">
            <CategoryItem class="capitalize" wrap v-for="mealType in mostLikedMeals"
                :label="mealType.name"
            />
            </section>
        </div>
        <MealWidget :meals="pageProps.mealTypes">
          <div class="mt-2 ">
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

            <SectionTitle type="secondary" class="text-center">
                Tomorrow
            </SectionTitle>
            <section class="flex mt-4">
                <CategoryItem wrap v-for="mealType in pageProps.mealTypes"
                    :label="`Add ${mealType.name}`"
                />
            </section>
          </div>
        </MealWidget>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Components/templates/AppLayout.vue";
import MealWidget from "@/Components/widgets/MealWidget.vue";
import MealTypeCell from "@/Components/molecules/MealTypeCell.vue";
import MealSectionNav from "@/Components/templates/MealSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import CategoryItem from "@/Components/mobile/CategoryItem.vue";
import WelcomeCard from "@/Components/organisms/WelcomeCard.vue";

import { usePage } from "@inertiajs/inertia-vue3";
import { addPlan } from "./utils";
import ChoppingListForm from "./Partials/ChoppingListForm.vue";

const props = defineProps({
  meals: {
    type: Object,
    required: true,
  },
  mostLikedMeals: {
    type: Array
  },
  ingredients: {
    type: Array
  },
  user: {
    type: Object,
    required: true,
  },

  categories: {
    type: Array,
    default() {
      return [];
    },
  },
  accounts: {
    type: Array,
    default() {
      return [];
    },
  },
});

const pageProps = usePage().props;

const getMealByType = (mealTypeId) => {
  return props.meals.data.find((mealPlan) => {
    return mealPlan.mealTypeId == mealTypeId;
  });
};

</script>
