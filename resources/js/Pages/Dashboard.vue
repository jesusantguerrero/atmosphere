<template>
    <AppLayout>
        <template #title v-if="contextStore.isMobile">
            <AppIcon size="medium" class="ml-2" />
        </template>

        <div class="px-5 mx-auto mt-5 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="md:w-9/12">
                <BudgetTracker
                    class="mt-5"
                    ref="budgetTrackerRef"
                    :budget="budgetTotal"
                    :expenses="transactionTotal.total"
                    :message="t('dashboard.welcome')"
                    :username="user.name"
                    @section-click="selected=$event"
                >
                    <ChartCurrentVsPrevious
                        v-if="selected=='expenses'"
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
                <OnboardingSteps :steps="onboarding.steps" :percentage="onboarding.percentage" class="mt-5" v-if="onboarding.steps" />
                <NextPaymentsWidget
                    v-else
                    class="w-full"
                    :payments="nextPayments"
                />
                <MealWidget :meals="meals?.data" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import { ref } from 'vue';

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import BudgetTracker from "@/Components/organisms/BudgetTracker.vue";
    import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
    import ChartComparison from "@/Components/widgets/ChartComparison.vue";
    import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";
    import MealWidget from "@/Components/widgets/MealWidget.vue";
    import WeatherWidget from "@/Components/widgets/WeatherWidget.vue";
    import NextPaymentsWidget from "@/Components/widgets/NextPaymentsWidget.vue";

    import { useAppContextStore } from '@/store';
import AppIcon from '@/Components/AppIcon.vue';

    const props = defineProps({
        revenue: {
            type: Object,
            default() {
                return {
                    previousYear: {
                        values: []
                    },
                    currentYear: {
                        values: []
                    }
                }
            }
        },
        expenses: {
            type: Object,
            default() {
                return {
                    previousYear: {
                        values: []
                    },
                    currentYear: {
                        values: []
                    }
                }
            }
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
                return []
            }
        },
        transactionTotal: {
            type: Object,
            default: 0,
        },
        categories: {
            type: Array,
            default() {
                return []
            }
        },
        accounts: {
            type: Array,
            default() {
                return []
            }
        },
        onboarding: {
            type: Array,
            default() {
                return []
            }
        }
    });
    const contextStore = useAppContextStore()

    const selected = ref(null);

    const budgetTrackerRef = ref();

</script>
