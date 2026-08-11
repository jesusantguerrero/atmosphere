<script lang="ts" setup>
    // Modernized settings section: a single self-contained card (header +
    // form body + optional actions footer) instead of the old two-column
    // Jetstream grid. Uses the app's surface tokens so it reads as one product
    // and works in light/dark. The #form slot keeps a 6-col grid so existing
    // `col-span-*` fields lay out unchanged.
    defineProps<{
        title?: string;
        description?: string;
    }>();

    defineEmits(["submitted"]);
</script>

<template>
    <section class="overflow-hidden border shadow-sm text-body rounded-xl border-base bg-base-lvl-3">
        <form @submit.prevent="$emit('submitted')">
            <div class="p-5 space-y-5 sm:p-6">
                <header v-if="title || description || $slots.title || $slots.description" class="space-y-1">
                    <h3 class="text-base font-semibold leading-none text-body">
                        <slot name="title">{{ title }}</slot>
                    </h3>
                    <p class="text-sm leading-relaxed text-body-1/60">
                        <slot name="description">{{ description }}</slot>
                    </p>
                </header>

                <div class="grid grid-cols-6 gap-x-4 gap-y-5">
                    <slot name="form" />
                </div>
            </div>

            <footer
                v-if="$slots.actions"
                class="flex items-center justify-end gap-3 px-5 py-3 border-t sm:px-6 bg-base-lvl-2/40 border-base"
            >
                <slot name="actions" />
            </footer>
        </form>
    </section>
</template>
