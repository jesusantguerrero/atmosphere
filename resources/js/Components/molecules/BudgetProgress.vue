
<template>
<div class="relative w-full overflow-hidden" :class="progressClass[1]">
    <div class="absolute z-20 flex items-center justify-center w-full h-full">
        <slot>
            <div>
                {{ progress }} % of {{ formatMoney(goal) }}
            </div>
        </slot>
    </div>
    <div class="absolute z-10 flex w-full h-full" :class="progressClass[0]" :style="progressStyle" />
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
    },
    progressClass: {
        type: Array,
        default: () => (["bg-primary", "bg-primary/40"]),
        validator(value) {
            return value?.length === 2;
        }
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
