<template>
 <AtButton class="border transition font-bold rounded-md min-w-max inline-block" :class="[typeClasses]">
    <slot />
 </AtButton>
</template>


<script setup>
import { computed } from 'vue';
import { AtButton } from "atmosphere-ui";

const variants = {
    primary: "bg-primary text-white",
    secondary: "bg-secondary text-white",
    inverse: "border-primary bg-primary/10 text-primary hover:bg-primary hover:text-white"
}

const props = defineProps({
    variant: {
        type: String,
        default: "primary"
    }
})

const typeClasses = computed(() => {
    return variants[props.variant] || variants.primary
})
</script>

