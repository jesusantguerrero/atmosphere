<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { budgetMutationError } from '../useBudget';

const page = usePage();

// Validation errors (422) from Inertia auto-populate $page.props.errors.
// budgetMutationError catches non-422 mutation errors set by handleBudgetError.
const message = computed(() => {
    const inertiaErrors = (page.props.errors ?? {}) as Record<string, string>;
    const firstInertiaError = Object.values(inertiaErrors)[0];
    return firstInertiaError || budgetMutationError.value;
});

const dismiss = () => {
    budgetMutationError.value = null;
};
</script>

<template>
    <div
        v-if="message"
        class="flex items-start justify-between gap-3 px-4 py-2 mb-2 text-sm rounded-md bg-error/10 text-error"
        role="alert"
    >
        <span class="flex-1">{{ message }}</span>
        <button
            type="button"
            class="text-error/70 hover:text-error focus:outline-none"
            :aria-label="$t('Dismiss error')"
            @click="dismiss"
        >
            ×
        </button>
    </div>
</template>
