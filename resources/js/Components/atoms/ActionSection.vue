<script setup lang="ts">
    // Modernized settings section (no form): header + content in one card,
    // matching FormSection's surface treatment. `danger` tints it as a
    // destructive "danger zone" (e.g. delete account).
    defineProps<{
        title?: string;
        description?: string;
        danger?: boolean;
    }>();
</script>

<template>
    <section
        class="border shadow-sm text-body rounded-xl"
        :class="danger ? 'border-error/40 bg-error/5' : 'border-base bg-base-lvl-3'"
    >
        <div class="p-5 space-y-5 sm:p-6">
            <header class="space-y-1">
                <h3
                    class="text-base font-semibold leading-none"
                    :class="danger ? 'text-error' : 'text-body'"
                >
                    <slot name="title">{{ title }}</slot>
                </h3>
                <p class="text-sm leading-relaxed text-body-1/60">
                    <slot name="description">{{ description }}</slot>
                </p>
            </header>
            <div>
                <slot name="content" />
            </div>
        </div>
    </section>
</template>
