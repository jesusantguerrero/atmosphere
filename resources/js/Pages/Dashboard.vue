<script setup lang="ts">
    import { ref, onMounted } from 'vue';

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
    import ChartComparison from "@/Components/widgets/ChartComparison.vue";
    import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";
    import WeatherWidget from "@/Components/widgets/WeatherWidget.vue";
    import AppIcon from '@/Components/AppIcon.vue';
    import DashboardDrafts from "./DashboardDrafts.vue";

    import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";
    import MealWidget from "@/domains/meal/components/MealWidget.vue";
    import BudgetTracker from "@/domains/budget/components/BudgetTracker.vue";
    import OccurrenceCard from '@/Components/Modules/occurrence/OccurrenceCard.vue';

    import { useAppContextStore } from '@/store';
    import { router } from '@inertiajs/vue3';

    defineProps({
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
        },
        checks: {
            type: Array
        }
    });
    const contextStore = useAppContextStore()

    const selected = ref(null);

    const budgetTrackerRef = ref();;


    const areChecksLoading = ref(true);
    const fetchChecks = () => {
        return router.reload({
            only: ['checks'],
            onFinish: () => {
                areChecksLoading.value = false
            }
        });
    }

    onMounted(() => {
        fetchChecks()
    })
</script>

<template>
    <AppLayout>
        <template #title v-if="contextStore.isMobile">
            <AppIcon size="medium" class="ml-2" />
        </template>

        <div class="px-5 mx-auto mt-5 mb-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="mt-6 md:w-9/12 space-y-4">
                <section class="flex flex-col md:flex-row md:space-x-4">
                    <BudgetTracker
                        class="md:w-8/12 w-full order-1  mt-2 md:mt-0"
                        ref="budgetTrackerRef"
                        :budget="budgetTotal"
                        :expenses="transactionTotal.total_amount"
                        :message="$t('dashboard.welcome')"
                        :username="user.name"
                        @section-click="selected=$event"
                    />
                    <WeatherWidget class="md:w-4/12 md:order-1" />
                </section>

                <ChartCurrentVsPrevious
                    v-if="selected=='expenses'"
                    class="w-full  md:mb-10 overflow-hidden bg-white rounded-lg"
                    :class="[cardShadow]"
                    :title="t('This month vs last month')"
                    ref="ComparisonRevenue"
                    :data="expenses"
                />

                <section class="flex space-x-4">
                    <ChartComparison
                        class="w-full md:mb-10 overflow-hidden bg-white rounded-lg"
                        :class="[cardShadow]"
                        :title="$t('Spending summary')"
                        ref="ComparisonRevenue"
                        :data="revenue"
                        data-item-total="total_amount"
                        :action="{
                            label: 'Go to Trends',
                            iconClass: 'fa fa-chevron-right',
                        }"
                        @action="router.visit('/trends/income-expenses-graph')"
                    />
                </section>
            </div>
            <div class="py-6  space-y-4 md:w-3/12">
                <OccurrenceCard :checks="checks" />
                <OnboardingSteps
                    v-if="onboarding.steps"
                    class="mt-5"
                    :steps="onboarding.steps"
                    :percentage="onboarding.percentage"
                />
                <NextPaymentsWidget
                    v-else
                    class="w-full"
                    :payments="nextPayments"
                />
                <MealWidget :meals="meals?.data" />

                <DashboardDrafts />
            </div>
        </div>
    </AppLayout>
</template>

