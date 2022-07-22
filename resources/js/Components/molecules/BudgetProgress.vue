
<template>
<div class="h-8 w-full rounded-b-lg bg-base-lvl-1 relative overflow-hidden">
<div class="w-full h-full flex items-center justify-center  absolute text-white z-20">
    {{ progress }}% of {{ formatMoney(goal) }}
</div>
    <div class="absolute h-full flex z-10 bg-primary-400 w-full" :style="progressStyle" />
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
    return Math.floor(props.current / props.goal * 100) || 0
})
const progressStyle = computed(() => {
    return {
        width: `${progress.value || 0 }%` || '0px'
    }
})
</script>
