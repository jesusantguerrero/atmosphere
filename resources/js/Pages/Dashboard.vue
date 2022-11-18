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
                    <ChartCurrentVsPrevious
                        class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
                        :class="[cardShadow]"
                        :title="t('Expenses summary')"
                        ref="ComparisonRevenue"
                        :data="props.revenue"
                    />

                    <NextPaymentsWidget
                        v-if="onboarding.steps"
                        class="w-4/12"
                        :payments="nextPayments"
                    />
                </div>

            </div>
            <div class="space-y-4 md:w-3/12 py-6">
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
    import { AtButton } from "atmosphere-ui";
    import { reactive, ref } from 'vue';
    import { groupBy } from "lodash";
    import { parseISO, format } from "date-fns";
    import { Inertia } from "@inertiajs/inertia";
    import { useI18n } from 'vue-i18n'

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import BudgetTracker from "@/Components/organisms/BudgetTracker.vue";
    import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
    import SectionTitle from "@/Components/atoms/SectionTitle.vue";
    import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
    import ChartComparison from "@/Components/widgets/ChartComparison.vue";
    import LogerButton from "@/Components/atoms/LogerButton.vue";
    import MealWidget from "@/Components/widgets/MealWidget.vue";
    import WeatherWidget from "../Components/widgets/WeatherWidget.vue";
    import NextPaymentsWidget from "@/Components/widgets/NextPaymentsWidget.vue";

    import { useSelect } from '@/utils/useSelects';
    import { transactionDBToTransaction } from "@/domains/transactions";
    import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";

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
