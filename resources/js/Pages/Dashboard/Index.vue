<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
    import WeatherWidget from "@/Components/widgets/WeatherWidget.vue";
    import AppIcon from '@/Components/AppIcon.vue';
    import DashboardDrafts from "./Partials/DashboardDrafts.vue";
    import WidgetContainer from '@/Components/WidgetContainer.vue';

    import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";
    import MealWidget from "@/domains/meal/components/MealWidget.vue";
    import BudgetTracker from "@/domains/budget/components/BudgetTracker.vue";
    import OccurrenceCard from '@/Components/Modules/occurrence/OccurrenceCard.vue';
    import DashboardSpendings from './Partials/DashboardSpendings.vue';

    import { useAppContextStore } from '@/store';
    import { IOccurrenceCheck } from '@/Components/Modules/occurrence/models';
    import { IAccount, ICategory } from '@/domains/transactions/models';

    withDefaults(defineProps<{
        spendingSummary: {
            previousYear: {
                values: []
            },
            currentYear: {
                values: []
            }
        },
        expenses: {
          previousYear: {
            values: []
          },
          currentYear: {
             values: []
          }
        },
        meals: { data: any[] },
        user: {
            name: string;
        },
        budgetTotal: number,
        nextPayments: any[],
        transactionTotal: Record<string, any>,
        categories: ICategory[],
        accounts: IAccount[],
        onboarding: Record<string, any>,
        checks: IOccurrenceCheck[]
    }>(), {

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

    const transactionsTabs = [{
      name: "next",
      label: "Next",
    },{
      name: "drafts",
      label: "Drafts",
    }];
</script>

<template>
    <AppLayout>
        <template #title v-if="contextStore.isMobile">
            <AppIcon size="medium" class="ml-2" />
        </template>

        <main class="px-5 mx-auto mt-5 mb-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <section class="mt-6 md:w-9/12 space-y-4">
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

                <DashboardSpendings
                    :expenses="expenses"
                    :spending-summary="spendingSummary"
                />
            </section>
            <section class="py-6  space-y-4 md:w-3/12">
                <OccurrenceCard :checks="checks" :wrap="true" />
                <OnboardingSteps
                    v-if="onboarding.steps"
                    class="mt-5"
                    :steps="onboarding.steps"
                    :percentage="onboarding.percentage"
                />

                <MealWidget :meals="meals?.data" />
                <WidgetContainer
                    :message="$t('Transactions')"
                    :tabs="transactionsTabs"
                    default-tab="next"
                    class="order-2 mt-4 lg:mt-0 lg:order-1"
                >
                    <template #actions>
                        <div id="transaction-actions" />
                    </template>
                    <template v-slot:content="{ selectedTab }">
                        <NextPaymentsWidget
                            v-if="selectedTab == 'next'"
                            class="w-full"
                            :payments="nextPayments"
                        />

                        <DashboardDrafts  v-else />
                    </template>
                </WidgetContainer>
            </section>
        </main>
    </AppLayout>
</template>
