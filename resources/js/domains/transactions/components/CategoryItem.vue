<script setup lang="ts">
import { useElementBounding } from '@vueuse/core';
import { ref, computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        default: 'Choose Category'
    },
    value: {
        type: [String, Number]
    },
    color: {

    },
    icon: {
        type: Object,
    },
    iconClass: {
        type: String,
        default: 'fas fa-tags'
    },
    colorClass: {
        default: 'group-hover:bg-base-lvl-2 bg-gray-400'
    },
    wrap: {
        type: Boolean
    }
})
const titleRef = ref(null)
const { width } = useElementBounding(titleRef)

const title = computed(() => {
    const chars = Math.floor(width.value / 3.5);
    return props.label.length > chars ? props.label.slice(0, props.label.length - chars) + ' ...' : props.label
})
</script>

<template>
    <button class="flex flex-col items-center justify-center w-32 group">
        <section
            class="relative overflow-hidden border-2 rounded-full h-14 w-14"
            :class="colorClass"
            :style="{backgroundColor: color}"
            :title="title"
        >
            <div class="w-1/2 h-full bg-white opacity-30" />
            <i :class="iconClass" v-if="iconClass" class="font-bold text-white" />
            <span class="absolute top-0 left-0 flex items-center justify-center w-full h-full font-bold text-white"> {{ value }}</span>
        </section>
        <p class="flex justify-center w-full mt-2 text-center h-[21px] overflow-hidden" ref="titleRef"
            :class="[wrap ? 'flex-wrap' : 'flex-nowrap']"
        > {{ title }} </p>
    </button>
</template>
