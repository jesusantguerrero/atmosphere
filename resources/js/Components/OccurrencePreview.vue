<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { formatDate } from '@/utils';
import { hasOccurrenceRule } from '@/domains/housing/occurrenceForm';

const props = defineProps<{ conditions: Record<string, any> }>();
const { t } = useI18n();
const transactions = ref<any[]>([]);
const loading = ref(false);
const error = ref('');
const hasRule = computed(() => hasOccurrenceRule(props.conditions));
let requestNumber = 0;
const preview = async () => {
    const request = ++requestNumber;
    error.value = '';
    transactions.value = [];
    if (!hasRule.value) { loading.value = false; return; }
    loading.value = true;
    try {
        const { data } = await axios.post('/housing/occurrences/preview', { conditions: props.conditions }, { timeout: 30000 });
        if (request === requestNumber) transactions.value = Array.isArray(data) ? data : [];
    } catch {
        if (request === requestNumber) error.value = t('Could not test this rule. Check the conditions and try again.');
    } finally {
        if (request === requestNumber) loading.value = false;
    }
};
watch(() => props.conditions, preview, { deep: true, immediate: true });
const dates = computed(() => [...new Set(transactions.value.map(row => String(row.date).slice(0, 10)))].sort());
const recentTransactions = computed(() => [...transactions.value].sort((first, second) => String(second.date).localeCompare(String(first.date))));
</script>

<template>
    <div class="space-y-4" :aria-busy="loading">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h3 class="font-semibold">{{ $t('Test your rule') }}</h3><p class="mt-1 text-sm text-body-1/70">{{ $t('These results use your current edits. Nothing is saved yet.') }}</p></div>
            <button v-if="hasRule" type="button" class="min-h-[44px] rounded-lg border border-base px-3 text-sm font-medium focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-50" :disabled="loading" @click="preview">{{ $t('Test again') }}</button>
        </div>
        <p v-if="!hasRule" class="rounded-lg bg-base-lvl-2 px-4 py-6 text-sm text-body-1/70">{{ $t('Add a description or category in Detection rule to see matching transactions.') }}</p>
        <p v-else-if="loading" role="status" class="py-8 text-center text-sm text-body-1/70">{{ $t('Finding matching transactions…') }}</p>
        <p v-else-if="error" role="alert" class="rounded-lg bg-danger/10 px-4 py-4 text-sm text-danger">{{ error }}</p>
        <template v-else>
            <div aria-live="polite" class="rounded-lg bg-base-lvl-2 px-4 py-3">
                <p class="font-semibold">{{ $t('{count} matching transactions', { count: transactions.length }) }}</p>
                <p v-if="dates.length" class="mt-1 text-sm text-body-1/70">{{ $t('{count} occurrence days', { count: dates.length }) }} · {{ $t('Last detected') }}: {{ formatDate(dates[dates.length - 1]) }}</p>
                <p v-else class="mt-1 text-sm text-body-1/70">{{ $t('Try a broader description or a different category. Only verified transactions are included.') }}</p>
            </div>
            <ul v-if="transactions.length" class="divide-y divide-base" :aria-label="$t('Matching transactions')">
                <li v-for="row in recentTransactions" :key="row.id" class="flex items-start justify-between gap-3 py-3 text-sm">
                    <div class="min-w-0"><p class="break-words font-medium">{{ row.concept || row.description }}</p><p class="mt-1 text-xs text-body-1/70">{{ row.category?.name || $t('Uncategorized') }}</p></div>
                    <time class="shrink-0 text-xs text-body-1/70" :datetime="String(row.date).slice(0, 10)">{{ formatDate(row.date) }}</time>
                </li>
            </ul>
            <p class="text-xs text-body-1/70">{{ $t('Saving applies this rule and updates the occurrence history.') }}</p>
        </template>
    </div>
</template>
