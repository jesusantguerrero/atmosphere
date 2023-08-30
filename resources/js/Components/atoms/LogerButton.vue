<script setup lang="ts">
import { computed } from 'vue';
// @ts-expect-error: no definitions
import { AtButton } from "atmosphere-ui";

const variants = {
    primary: "bg-primary text-white",
    secondary: "bg-secondary text-white",
    neutral: "bg-base-lvl-2 text-primary border-base-lvl-1",
    inverse: "border-primary bg-primary/10 text-primary hover:bg-primary hover:text-white",
    'inverse-secondary': "border-secondary bg-secondary/10 text-secondary hover:bg-secondary hover:text-white"

}

const props = defineProps({
    variant: {
        type: String,
        default: "primary"
    },
    as: {
        type: [Object, String],
        default: AtButton,
    },
    processing: {
        type: Boolean
    }
})

const typeClasses = computed(() => {
    return variants[props.variant] || variants.primary
})
</script>

<template>
 <component :is="as" class="relative flex items-center px-5 py-2 font-bold transition border rounded-md min-w-max" :class="[typeClasses]" :disabled="processing">
    <slot />
 </component>
</template>


