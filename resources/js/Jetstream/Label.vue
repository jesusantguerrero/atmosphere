<template>
    <label class="block font-medium text-sm text-body">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot></slot></span>
    </label>
</template>

<script setup>
    defineProps({
        value: {
            type: String
        }
    })
</script>
