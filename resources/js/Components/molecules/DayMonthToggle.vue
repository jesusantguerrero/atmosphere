<script setup lang="ts">
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

// Today (day view) and Calendar (month view) are the same data at different
// granularities — surfaced as a Summary/Detailed-style toggle instead of two
// sidebar entries. Active view is inferred from the current pathname.
const page = usePage();

const currentView = computed<'day' | 'month'>(() => {
    const path = (page.url as string)?.split('?')[0] ?? '';
    return path.startsWith('/calendar') ? 'month' : 'day';
});

const setView = (view: 'day' | 'month'): void => {
    if (view === currentView.value) return;
    router.visit(view === 'day' ? '/today' : '/calendar', { preserveScroll: false });
};
</script>

<template>
    <div class="flex gap-1 bg-base-lvl-2 rounded-lg p-0.5">
        <button
            type="button"
            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
            :class="currentView === 'day'
                ? 'bg-base-lvl-3 text-body shadow-sm'
                : 'text-body-1/60 hover:text-body-1'"
            @click="setView('day')"
        >
            {{ $t('Day') }}
        </button>
        <button
            type="button"
            class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
            :class="currentView === 'month'
                ? 'bg-base-lvl-3 text-body shadow-sm'
                : 'text-body-1/60 hover:text-body-1'"
            @click="setView('month')"
        >
            {{ $t('Month') }}
        </button>
    </div>
</template>
