<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(defineProps<{
    type: string
}>(), {
    type:"default",
});
const types: Record<string, string> = {
    default: "text-primary",
    primary: "text-primary",
    secondary: "text-secondary",
};

const textColor = computed(() => {
    return types[props.type] || types.default;
});
</script>

<template>
    <h4 class="`text-lg font-bold" :class="textColor">
        <slot></slot>
    </h4>
</template>

