<script lang="ts" setup>
    import JetSectionTitle from './SectionCardTitle.vue';

    defineProps<{
        title?: string;
        description?: string;
    }>();

    defineEmits(["submitted"]);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <JetSectionTitle>
            <template #title><slot name="title">{{ title }}</slot></template>
            <template #description><slot name="description">{{ description }}</slot></template>
        </JetSectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')" class="text-base-100">
                <div class="px-4 py-5 shadow bg-base-lvl-3 sm:p-6"
                    :class="$slots.actions ? 'sm:rounded-tl-md sm:rounded-tr-md' : 'sm:rounded-md'">
                    <div class="">
                        <slot name="form"></slot>
                    </div>
                </div>

                <div class="flex items-center justify-end px-4 py-3 text-right shadow bg-base-lvl-1 sm:px-6 sm:rounded-bl-md sm:rounded-br-md" v-if="$slots.actions">
                    <slot name="actions"></slot>
                </div>
            </form>
        </div>
    </div>
</template>
