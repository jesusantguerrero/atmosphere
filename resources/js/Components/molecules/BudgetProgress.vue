
<template>
<div class="relative w-full h-8 overflow-hidden rounded-b-lg bg-primary/40">
<div class="absolute z-20 flex items-center justify-center w-full h-full text-white">
    {{ progress }}% of {{ formatMoney(goal) }}
</div>
    <div class="absolute z-10 flex w-full h-full text-white bg-primary" :style="progressStyle" />
</div>
</template>

<script setup>
import formatMoney from '@/utils/formatMoney';
import { computed } from 'vue';

const props = defineProps({
    goal: {
        type: Number
    },
    current: {
        type: Number,
        default: 0
    }
})

const progress = computed(() => {
    let progressValue = Math.floor(props.current / props.goal * 100) || 0
    progressValue = Number.isFinite(progressValue) ? progressValue : 0
    return progressValue
})
const progressStyle = computed(() => {
    return {
        width: `${progress.value || 0 }%` || '0px'
    }
})
</script>
