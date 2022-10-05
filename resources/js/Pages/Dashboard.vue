<template>
    <AppLayout>
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
                />

                <div class="flex space-x-4">
                    <ChartComparison
                        class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg shadow-xl"
                        :title="t('Expenses summary')"
                        ref="ComparisonRevenue"
                        :data="props.revenue"
                    />

                    <div
                        class="w-4/12 mt-4 mb-10 overflow-hidden bg-white shadow-xl sm:rounded-lg"
                    >
                        <h4 class="p-4 font-bold">Next Payments</h4>
                        <div class="payment" v-for="payment in nextPayments" :key="payment.id">
                            {{ payment.name }}
                        </div>
                    </div>

                </div>

            </div>
            <div class="space-y-4 md:w-3/12">
                <OnboardingSteps :steps="onboarding.steps" :percentage="onboarding.percentage" class="mt-5" />
                <div class="pb-10 space-y-5">
                    <SectionTitle type="secondary"> {{ t('Menu for today') }}</SectionTitle>
                    <RandomMealCard />
                    <div class="px-4 py-2 space-y-4 rounded-md shadow-xl cursor-pointer min-h-min bg-base-lvl-3">
                        <template v-if="meals.data.length">
                            <div v-for="plannedMeal in meals.data" :key="plannedMeal.id">
                                <h4 class="font-bold capitalize text-body-1">
                                    {{ plannedMeal.name }}
                                </h4>
                                <small class="capitalize text-primary">{{ plannedMeal.mealTypeName }}</small>
                            </div>
                        </template>
                        <div v-else class="py-1.5 text-center">
                            <h4 class="py-1 text-2xl font-bold text-body-1"> {{t('No meals') }} </h4>
                            <LogerButton variant="inverse">{{ t('Go to planner')}}</LogerButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import { reactive, ref } from 'vue';
    import { groupBy } from "lodash";
    import { parseISO, format } from "date-fns";
    import { Inertia } from "@inertiajs/inertia";
    import { useI18n } from 'vue-i18n'

    import AppLayout from '@/Layouts/AppLayout.vue'
    import BudgetTracker from "@/Components/organisms/BudgetTracker.vue";
    import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
    import SectionTitle from "@/Components/atoms/SectionTitle.vue";
    import RandomMealCard from "@/Components/RandomMealCard.vue";
    import OnboardingSteps from "@/Components/OnboardingSteps.vue";
    import ChartComparison from "@/Components/ChartComparison.vue";
    import LogerButton from "@/Components/atoms/LogerButton.vue";

    import { useSelect } from '@/utils/useSelects';
    import { transactionDBToTransaction } from "@/domains/transactions";

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
        meals: {
            type: Array,
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

    const selected = ref(null);
    const { t } = useI18n()

    const budgetTrackerRef = ref();
    const handleEdit = (transaction) => {
        budgetTrackerRef.setTransaction(transaction)
    }
</script>
