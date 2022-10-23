<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <JetSectionTitle>
            <template #title><slot name="title">{{ title }}</slot></template>
            <template #description><slot name="description">{{ description }}</slot></template>
        </JetSectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')" class="text-base-100">
                <div class="px-4 py-5 bg-base-lvl-3 sm:p-6 shadow"
                    :class="hasActions ? 'sm:rounded-tl-md sm:rounded-tr-md' : 'sm:rounded-md'">
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form"></slot>
                    </div>
                </div>

                <div class="flex items-center justify-end px-4 py-3 bg-base-lvl-1 text-right sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md" v-if="hasActions">
                    <slot name="actions"></slot>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import JetSectionTitle from './SectionTitle.vue'

    export default {
        props: {
            title: {
                type: String
            },
            description: {
                type: String
            }
        },
        emits: ['submitted'],

        components: {
            JetSectionTitle,
        },

        computed: {
            hasActions() {
                return !! this.$slots.actions
            }
        }
    }
</script>
