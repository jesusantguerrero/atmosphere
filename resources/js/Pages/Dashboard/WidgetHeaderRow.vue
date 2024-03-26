<script setup lang="ts">
defineProps<{
    title: string;
    valueBadge: number|string;
    value: number|string;
    valueSubtitle: string;
}>()
</script>

<template>
<header class="mt-4 border-t py-4 flex items-start justify-between pb-2">
    <h1 class="font-bold flex items-center w-full">
        <section class="bg-white text-primary w-8 h-8 rounded-full flex items-center justify-center mr-2"
        >
            <slot name="icon" />
        </section>
        {{ title }}
    </h1>
    <section class="space-x-2 w-full">
        <h2 class="text-lg flex items-center  font-bold">
            <span class="bg-white rounded-md text-error text-xs px-1 py-0.5 mr-1" v-if="valueBadge">
                {{ valueBadge }}
            </span>
            <span>
                {{ value }}
            </span>
        </h2>
        <p class="text-xs" v-if="valueSubtitle">
            <slot name="value-subheader">
                {{ valueSubtitle }}
            </slot>
        </p>
    </section>
</header>
</template>

