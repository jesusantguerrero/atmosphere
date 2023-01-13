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
        <BudgetTracker
          class="mt-5"
          ref="budgetTrackerRef"
          :budget="budgetTotal"
          :expenses="transactionTotal.total"
          :message="t('dashboard.welcome')"
          :username="user.name"
          @section-click="selected = $event"
        >
          <ChartCurrentVsPrevious
            v-if="selected == 'expenses'"
            class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
            :class="[cardShadow]"
            :title="t('This month vs last month')"
            ref="ComparisonRevenue"
            :data="expenses"
          />
        </BudgetTracker>

        <div class="flex space-x-4">
          <ChartComparison
            class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
            :class="[cardShadow]"
            :title="t('Spending summary')"
            ref="ComparisonRevenue"
            :data="revenue"
          />
        </div>
      </div>
      <div class="py-6 space-y-4 md:w-3/12">
        <WeatherWidget />
        <OnboardingSteps
          :steps="onboarding.steps"
          :percentage="onboarding.percentage"
          class="mt-5"
          v-if="onboarding.steps"
        />
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
          </div>
        </MealWidget>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from "vue";

import AppLayout from "@/Components/templates/AppLayout.vue";
import BudgetTracker from "@/Components/organisms/BudgetTracker.vue";
import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";
import MealWidget from "@/Components/widgets/MealWidget.vue";
import WeatherWidget from "@/Components/widgets/WeatherWidget.vue";
import NextPaymentsWidget from "@/Components/widgets/NextPaymentsWidget.vue";

import { useAppContextStore } from "@/store";
import AppIcon from "@/Components/AppIcon.vue";
import MealTypeCell from "@/Components/molecules/MealTypeCell.vue";
import { usePage } from "@inertiajs/inertia-vue3";
import { addPlan } from "./utils";
import MealSectionNav from "@/Components/templates/MealSectionNav.vue";
import { AtButton } from "atmosphere-ui";
import LogerButton from "@/Components/atoms/LogerButton.vue";

const props = defineProps({
  revenue: {
    type: Object,
    default() {
      return {
        previousYear: {
          values: [],
        },
        currentYear: {
          values: [],
        },
      };
    },
  },
  expenses: {
    type: Object,
    default() {
      return {
        previousYear: {
          values: [],
        },
        currentYear: {
          values: [],
        },
      };
    },
  },
  meals: {
    type: Object,
    required: true,
  },
  user: {
    type: Object,
    required: true,
  },
  budgetTotal: {
    type: Number,
    default: 0,
  },
  nextPayments: {
    type: Array,
    default() {
      return [];
    },
  },
  transactionTotal: {
    type: Object,
    default: 0,
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
  onboarding: {
    type: Array,
    default() {
      return [];
    },
  },
});

const pageProps = usePage().props;

const contextStore = useAppContextStore();

const selected = ref(null);

const budgetTrackerRef = ref();

const getMealByType = (mealTypeId) => {
  return props.meals.data.find((mealPlan) => {
    return mealPlan.mealTypeId == mealTypeId;
  });
};

</script>
