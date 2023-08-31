<script setup lang="ts">
const {
    textClass = 'text-2xl font-bold' 
} = defineProps<{
    value: string;
    label?: string;
    textClass?: string;
    labelClass?: string;
}>();
</script>

<template>
    <div class="relative flex flex-col justify-center w-full px-4 py-5 text-center rounded-md shadow-md cursor-pointer text-body-1 bg-base-lvl-3">
        <div class="absolute background-icon opacity-40 right-14"><!----></div>
        <div :class="textClass" class="font-bold">
            {{ value }} 
            <small class="block text-sm" :class="labelClass">{{ label }}</small>
        </div>
        <div class="mt-2">

        </div>
    </div>
</template>