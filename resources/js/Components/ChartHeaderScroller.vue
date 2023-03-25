<script  lang="ts" setup>
import { useElementBounding, useScroll } from '@vueuse/core';
import { computed, nextTick, ref, watch } from 'vue';

const props = withDefaults(defineProps<{
    itemClass: string;
    steps: number
}>(), {
    steps: 3
});

const presenterRef = ref();
const { width } = useElementBounding(presenterRef);
const { x: scrollX } = useScroll(presenterRef)

const scrollLeft = ref(0)
const updateLeft = () => {
    scrollLeft.value = presenterRef.value.scrollLeft;
}


const hasRightScroll = computed(() => {
    return scrollLeft.value + width.value !== presenterRef.value?.scrollWidth;
})

const itemSize = ref(0);
watch(() => props.itemClass, () => {
    nextTick(() => {
        const item = document.querySelector('.' + props.itemClass)
        itemSize.value = item?.clientWidth ?? 0
    })
}, { immediate: true })

const nextSlide = () => {
    scrollX.value += (itemSize.value * props.steps);
}

const previousSlide = () => {
    scrollX.value -= (itemSize.value * props.steps);
}
</script>

<template>
<header class="relative">
    <button v-if="scrollLeft !== 0"
    class="absolute z-10 top-1/3 opacity-40 hover:opacity-100 transition bg-primary h-10 w-10 text-white text-2xl flex items-center justify-center rounded-full left-0"
    @click="previousSlide()"
    >
        <IMdiChevronLeft />
    </button>
    <div ref="presenterRef" @scroll="updateLeft" class="flex w-full relative snap-x overflow-hidden thumb-transparent h-32 px-10 scroll-smooth text-body-1/50 space-x-2 divide-x divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2 mb-2">
        <slot />
    </div>
    <button v-if="hasRightScroll"
        class="absolute top-1/3 opacity-40 hover:opacity-100 transition bg-primary h-10 w-10 text-white text-2xl flex items-center justify-center rounded-full right-0"
        @click="nextSlide()"
    >
        <IMdiChevronRight />
    </button>
</header>
</template>


