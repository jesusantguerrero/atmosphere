<template>
    <button class="w-32 flex items-center justify-center flex-col group">
        <section class="rounded-full overflow-hidden relative h-14 w-14 border-2" :class="colorClass" :style="{backgroundColor: color}">
            <div class="h-full w-1/2 bg-white opacity-30" />
            <i :class="iconClass" v-if="iconClass" class="text-white font-bold" />
        </section>
        <p class="mt-2 flex w-full text-center justify-center" ref="titleRef"
            :class="[wrap ? 'flex-wrap' : 'flex-nowrap']"
        > {{ title }}</p>
    </button>
</template>

<script setup>
import { useElementBounding } from '@vueuse/core';
import { ref, computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        default: 'Choose Category'
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
