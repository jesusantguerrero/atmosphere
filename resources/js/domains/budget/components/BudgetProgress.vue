
<script setup lang="ts">
import formatMoney from '@/utils/formatMoney';
import { computed } from 'vue';

const props = defineProps({
    goal: {
        type: [Number, String],
    },
    current: {
        type: [Number, String],
        default: 0
    },
    filled: {
        type: [Number, String],
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
    let progressValue = ((parseFloat(props.current || 0)) / (parseFloat(props.goal || 0)) || 0) * 100;
    progressValue = Number.isFinite(progressValue) ? progressValue : 0;

  return Math.round((progressValue + Number.EPSILON) * 100) / 100;
})
const filledProgress = computed(() => {
    let progressValue = ((parseFloat(Math.abs(props.filled) || 0)) / (parseFloat(props.current || 0)) || 0) * 100;
    progressValue = Number.isFinite(progressValue) ? progressValue : 0;

  return Math.round((progressValue + Number.EPSILON) * 100) / 100;
})

const progressStyle = computed(() => {
    return {
        width: `${progress.value || 0 }%` || '0px'
    }
})
const filledStyle = computed(() => {
    return {
        width: `${filledProgress.value || 0 }%` || '0px'
    }
})
</script>


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
    <div class="absolute z-10 flex w-full h-full" :class="progressClass[0]" :style="progressStyle">
        <div class="w-full relative">
            <div class="absolute z-20 flex w-full h-full bg-spent" :style="filledStyle" />
        </div>
    </div>
</div>

<div>
    <slot  name="after" :progress="progress" />
</div>
</template>

<style lang="scss">
.bg-spent {
    background: repeating-linear-gradient(
        45deg,
        rgba(255,255,255, .6),
        rgba(255,255,255, .6) 10px,
        rgba(255,255,255, .2) 10px,
        rgba(255,255,255, .2) 20px
    );
}
</style>
