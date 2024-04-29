<script setup lang="ts">
    import { onMounted, ref, computed } from 'vue';
    import axios from 'axios';

    import WidgetContainer from '@/Components/WidgetContainer.vue';
    import BudgetProgress from '@/domains/budget/components/BudgetProgress.vue';
    import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';

    const budgetData = ref({})

    const fetchChecks = () => {
        return axios.get(`/budget-funds?json=true`).then(({ data }) => {
            budgetData.value = data?.at(0);
        });
    }


    const fundMetrics = computed(() => ({
        target: budgetData?.value.monthlyExpense * budgetData?.value.target_times,
        balance: budgetData.value?.balance ?? 0
    }))

    onMounted(() => {
        fetchChecks()
    })
</script>

<template>
    <WidgetContainer
        :message="$t('Emergency Fund Builder')"
    >
        <template #actions>
            <section class="group cursor-default mr-5">
                <span class="group-hover:flex hidden">
                    <MoneyPresenter :value="fundMetrics.target" />
                </span>
                <span class="group-hover:hidden">
                    {{ budgetData.target_times }} Months
                </span>
            </section>
        </template>

        <template #content>
            <section class="my-2">
                <BudgetProgress
                    class="h-1.5 rounded-sm"
                    :goal="fundMetrics.target"
                    :current="budgetData.balance"
                    :progress-class="['bg-primary', 'bg-secondary/5']"
                    :show-labels="false"
                >
                 <template v-slot:before="{ progress }">
                    <header class="mb-1 font-bold flex justify-between">
                        <section class="group cursor-default">
                            <span class="group-hover:flex hidden"> <MoneyPresenter :value="fundMetrics.balance" /></span>
                            <span  class="group-hover:hidden"> {{ progress}}% </span>
                        </section>
                        <span>
                            {{ budgetData.total?.toFixed?.(2) }} Months
                        </span>
                    </header>
                </template>

                    <template v-slot:after="{ progress }">
                    <div class="flex justify-between w-full mt-1">
                        <span>Monthly Splits </span>
                        <span> <MoneyPresenter :value="budgetData.monthly_splits" /></span>
                    </div>
                </template>
                </BudgetProgress>
            </section>
        </template>
    </WidgetContainer>
</template>

