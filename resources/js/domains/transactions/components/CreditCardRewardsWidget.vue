<script setup lang="ts">
import { computed, ref } from 'vue';

import DonutChart from '@/Components/organisms/DonutChart.vue';
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';

const props = withDefaults(defineProps<{
    creditCardData: any[];
}>(), {});

const isReward = ref(true);
const toggleType = () => {
    isReward.value = !isReward.value
}

const fieldValue = computed(() => {
    return isReward.value ? 'total' : 'discounts';
})
const typeLabel = computed(() => {
    return isReward.value ? 'Total credit card usage' : 'Total rewards';
})
</script>

<template>
<WidgetTitleCard
    :title="typeLabel"
    @action="$emit('action')"
>
    <template #afterActions>
        <button @click="toggleType" class="transform animate" :class="[!isReward && '-rotate-180']">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--ic" width="32" height="32" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M12 7C6.48 7 2 9.24 2 12c0 2.24 2.94 4.13 7 4.77V20l4-4l-4-4v2.73c-3.15-.56-5-1.9-5-2.73c0-1.06 3.04-3 8-3s8 1.94 8 3c0 .73-1.46 1.89-4 2.53v2.05c3.53-.77 6-2.53 6-4.58c0-2.76-4.48-5-10-5z"></path></svg>
        </button>
    </template>
    <article class="w-full">
        <DonutChart
            class="mx-auto flex justify-center"
            style="height:270px; background: white; width: 100%"
            :series="creditCardData"
            label="name"
            :legend="true"
            :value="fieldValue"
        />
    </article>
</WidgetTitleCard>

</template>


