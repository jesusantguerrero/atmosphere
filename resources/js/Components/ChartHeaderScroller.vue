<script  lang="ts" setup>
import { useElementBounding, useScroll } from '@vueuse/core';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

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
    return scrollLeft.value + width.value < presenterRef.value?.scrollWidth;
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

const scrollAllLeft = () => {

}
const scrollAllRight = () => {
    scrollX.value =  presenterRef.value?.scrollWidth + width.value;
}

const opacity = ref(0);

onMounted(() => {
    scrollAllRight()
    opacity.value = 1;
})

const opacityStyles = computed(() => {
    return {
        'opacity': opacity.value
    }
})
</script>

<template>
<header class="relative" :style="opacityStyles">
    <button v-if="scrollLeft !== 0"
    class="absolute left-0 z-10 flex items-center justify-center w-10 h-10 text-2xl text-white transition rounded-full top-1/3 opacity-40 hover:opacity-100 bg-primary"
    @click="previousSlide()"
    >
        <IMdiChevronLeft />
    </button>
    <div ref="presenterRef" @scroll="updateLeft" :class="{'scroll-smooth': opacity}" class="relative flex w-full h-32 px-10 mb-2 space-x-2 overflow-hidden divide-x snap-x thumb-transparent text-body-1/50 divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2">
        <slot />
    </div>
    <button v-if="hasRightScroll"
        class="absolute right-0 flex items-center justify-center w-10 h-10 text-2xl text-white transition rounded-full top-1/3 opacity-40 hover:opacity-100 bg-primary"
        @click="nextSlide()"
    >
        <IMdiChevronRight />
    </button>
</header>
</template>


