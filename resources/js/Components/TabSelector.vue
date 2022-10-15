<template>
    <div class="space-y-4">
        <div
            v-for="option in options"
            :key="option.value" @click="$emit('update:modelValue', option.value)"
            class="py-4 border px-4 font-bold hover:border-primary hover:bg-primary/10 rounded-md cursor-pointer transition text-body-1/80 hover:text-primary"
        >
            {{ option.label }}
        </div>
    </div>
</template>

<script setup>
    const props = defineProps({
        modelValue: {
            type: String
        },
        options: {
            type: Array,
            required: true
        }
    })
</script>
