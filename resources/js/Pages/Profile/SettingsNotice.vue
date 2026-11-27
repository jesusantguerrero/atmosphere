<script setup lang="ts">
    // The one thing a settings page wants you to do now (turn on two-step
    // verification, answer an invitation), shown above everything else.
    withDefaults(defineProps<{
        title: string;
        description?: string;
        tone?: 'warn' | 'primary';
    }>(), {
        tone: 'warn',
    });
</script>

<template>
    <div
        class="flex flex-col gap-4 p-4 border rounded-xl sm:flex-row sm:items-center"
        :class="tone === 'warn' ? 'border-warning/50 bg-warning/5' : 'border-primary/50 bg-primary/5'"
        role="status"
    >
        <div class="flex items-start flex-1 min-w-0 gap-3">
            <span class="mt-0.5 text-xl" :class="tone === 'warn' ? 'text-warning' : 'text-primary-ink'">
                <slot name="icon" />
            </span>
            <div class="min-w-0">
                <p class="font-bold text-body">{{ title }}</p>
                <p v-if="description" class="text-sm text-body-1/70">{{ description }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <slot />
        </div>
    </div>
</template>
