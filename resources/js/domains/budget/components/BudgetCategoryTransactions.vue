<script setup lang="ts">
    import ChartComparison from "@/Components/widgets/ChartComparison.vue";
    import { format, startOfMonth, subMonths } from "date-fns";
    import { ref, watch } from "vue";

    const props = defineProps<{
        categoryId: number
    }>();


    const spending = ref({
        transactions: []
    });
    
    const fetchSpending = async (categoryId: number) => {
        const today = format(new Date(), 'yyyy-MM-dd');
        const lastTwelve = format(startOfMonth(subMonths(new Date(), 12)), 'yyyy-MM-dd');

        const response = await axios.get(`/api/category-transactions/${categoryId}`, {
            params: {
                filter: {
                    date: `${lastTwelve}~${today}` 
                },
            }
        })

        console.log(response)
        
        spending.value = response.data;
    }

    watch(() => props.categoryId, () => {
        fetchSpending(props.categoryId)
    }, { immediate: true })
</script>

<template>
    <ChartComparison
        v-if="spending.transactions"
        class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
        :title="$t('Spending summary')"
        ref="ComparisonRevenue"
        :data="spending.transactions"
    />
</template>

