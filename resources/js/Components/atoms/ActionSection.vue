<template>
    <div class="md:grid md:grid-cols-3 md:gap-6 text-body">
        <JetSectionTitle>
            <template #title><slot name="title">{{ title }}</slot></template>
            <template #description><slot name="description">{{ description }}</slot></template>
        </JetSectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 shadow sm:p-6 bg-base-lvl-3 sm:rounded-lg text-base-200">
                <slot name="content"></slot>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
    import JetSectionTitle from './SectionCardTitle.vue'
    defineProps(['title', 'description'])
</script>
