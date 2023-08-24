<script setup lang="ts">
    import NumberHider from '@/Components/molecules/NumberHider.vue';
    import { useElementHover } from "@vueuse/core"
    import { computed, ref } from 'vue';

    const props = defineProps({
        value: {
            type: String,
        },
        title: {
            type: String,
        },
        varianceTitle: {
            type: String,
        },
        variance: {
            type: [String, Number],
        },
        varianceAmount: {
            type: [String, Number]
        }
    });

    const varianceRef = ref();
    const isHovered = useElementHover(varianceRef)
    const lastMonthValue = computed(() => {
        return !isHovered.value ? `${props.variance}%` : props.varianceAmount;
    });
</script>


<template>
<div class="px-5 py-5 mx-auto text-white cursor-pointer bg-primary rounded-xl" @click="$emit('click')">
    <header class="flex justify-between">
        <h4> {{ title }} </h4>
        <span><i class="fa fa-cogs"></i></span>
    </header>
    <div class="relative mt-2 text-lg font-bold">
        <NumberHider />
        {{ value }}
    </div>
    <div class="px-5 py-2 mt-4 text-xs bg-gray-700 bg-opacity-25 rounded-3xl" ref="varianceRef">
        {{ varianceTitle }}:
        <span class="font-bold">{{ lastMonthValue }}</span>
    </div>
</div>
</template>
