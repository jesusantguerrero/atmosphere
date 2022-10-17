
<template>
<div>
    <slot  name="before" :progress="progress" />
</div>

<div v-bind="$attrs" class="relative w-full overflow-hidden" :class="progressClass[1]">
    <div class="absolute z-20 flex items-center justify-center w-full h-full">
        <slot>
            <div v-if="showLabels">
                {{ progress }} % of {{ formatMoney(goal) }}
            </div>
        </slot>
    </div>
    <div class="absolute z-10 flex w-full h-full" :class="progressClass[0]" :style="progressStyle" />
</div>

<div>
    <slot  name="after" :progress="progress" />
</div>
</template>

<script setup>
import formatMoney from '@/utils/formatMoney';
import ExactMath from 'exact-math';
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
    },
    showLabels: {
        type: Boolean,
        default: true
    }
})

const progress = computed(() => {
    let progressValue = ExactMath.mul(ExactMath.div(props.current, props.goal), 100) || 0
    progressValue = Number.isFinite(progressValue) ? progressValue : 0
    return Math.round((progressValue + Number.EPSILON) * 100) / 100
})

const progressStyle = computed(() => {
    return {
        width: `${progress.value || 0 }%` || '0px'
    }
})
</script>
