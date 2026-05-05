<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    // 'error' = red (overspent / failure), 'warning' = amber (underfunded / heads-up)
    color?: 'error' | 'warning';
    title?: string;
}>(), {
    color: 'error',
});

const colorClass = computed(() => ({
    error: 'bg-error ring-error/20',
    warning: 'bg-warning ring-warning/20',
}[props.color] ?? 'bg-error ring-error/20'));
</script>

<template>
    <div
        class="absolute -top-1 -right-2 h-2 w-2 rounded-full ring animate-pulse"
        :class="colorClass"
        :title="title"
    />
</template>
