<template>
    <h4 class="`text-lg font-bold" :class="textColor">
        <slot></slot>
    </h4>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    type: {
        type: String,
        default: "default",
    },
});
const textColor = computed(() => {
    const types = {
        default: "text-primary",
        primary: "text-primary",
        secondary: "text-secondary",
    };
    return types[props.type] || types.default;
});
</script>
