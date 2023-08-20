<script setup lang="ts">
    import ChartComparison from "@/Components/widgets/ChartComparison.vue";
    import { onMounted } from "vue";
    import { useAppContextStore } from '@/store';

    const props = defineProps<{
        categoryId: number
    }>();
    const contextStore = useAppContextStore()


    onMounted(() => {
        getSpendings(props.categoryId)
    })
</script>

<template>
    <ChartComparison
        class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg"
        :class="[cardShadow]"
        :title="t('Spending summary')"
        ref="ComparisonRevenue"
        :data="spendings"
    />
</template>

