<script setup lang="ts">
    import { onMounted, ref } from 'vue';

    import WidgetContainer from '@/Components/WidgetContainer.vue';
    import axios from 'axios';
import { formatMoney } from '@/utils';

    const budgetData = ref({})

    const fetchChecks = () => {
        return axios.get(`/budget-funds?json=true`).then(({ data }) => {
            budgetData.value = data?.at(0);
        });
    }

    onMounted(() => {
        fetchChecks()
    })
</script>

<template>
    <WidgetContainer
        :message="$t('Emergency Fund Builder')"
    >
        <template #content>
            <section>
                <p>
                    {{ formatMoney(budgetData.balance) }}
                </p>
                <p>
                    {{ formatMoney(budgetData.monthlyExpense) }}
                </p>
                <p>
                    {{ budgetData.total?.toFixed?.(2) }} Months
                </p>
            </section>
        </template>
    </WidgetContainer>
</template>

