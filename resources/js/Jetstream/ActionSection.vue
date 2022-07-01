<template>
    <div class="md:grid md:grid-cols-3 md:gap-6 text-slate-100">
        <jet-section-title>
            <template #title><slot name="title">{{ title }}</slot></template>
            <template #description><slot name="description">{{ description }}</slot></template>
        </jet-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 sm:p-6 bg-slate-600 shadow sm:rounded-lg text-slate-200">
                <slot name="content"></slot>
            </div>
        </div>
    </div>
</template>

<script setup>
    import JetSectionTitle from './SectionTitle.vue'
    defineProps(['title', 'description'])
</script>
