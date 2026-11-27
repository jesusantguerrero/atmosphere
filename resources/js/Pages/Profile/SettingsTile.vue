<script setup lang="ts">
    // One status at a glance on a settings page: what it is, how it stands and,
    // below, the action that changes it. `tone` colors the value.
    withDefaults(defineProps<{
        label: string;
        value: string;
        hint?: string;
        tone?: 'ok' | 'warn' | 'neutral';
    }>(), {
        tone: 'neutral',
    });
</script>

<template>
    <div
        class="flex flex-col gap-2 p-4 border rounded-xl"
        :class="tone === 'warn' ? 'border-warning/50 bg-warning/5' : 'border-base bg-base-lvl-3'"
    >
        <div class="flex items-center gap-2 text-sm text-body-1/70">
            <slot name="icon" />
            {{ label }}
        </div>
        <div
            class="text-lg font-bold"
            :class="{ 'text-success': tone === 'ok', 'text-warning': tone === 'warn', 'text-body': tone === 'neutral' }"
        >
            {{ value }}
        </div>
        <div class="flex items-end mt-auto min-h-9">
            <slot>
                <span v-if="hint" class="text-sm text-body-1/70">{{ hint }}</span>
            </slot>
        </div>
    </div>
</template>
