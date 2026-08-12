<script lang="ts" setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import TransactionsList from '@/domains/transactions/components/TransactionsList.vue';
import { removeTransaction, useTransactionModal } from '@/domains/transactions';
import { useTransactionStore } from '@/store/transactions';
// @ts-expect-error: no types
import MdiSync from '~icons/mdi/sync';
import { useI18n } from 'vue-i18n';

// The Inbox IS the review screen for DRAFT transactions (captured, not yet
// confirmed). It reuses the same TransactionsList + confirm/remove machinery as
// the dashboard draft widget, so every row is actionable here — no dead links.
const drafts = ref<any[]>([]);
const isLoading = ref(true);
const selected = ref([]);
const listRef = ref();
const { t } = useI18n();
// Same Inertia-form delete used by the dashboard/finance bulk delete, pointed
// at the shared /finance/transactions/bulk/delete endpoint.
const deleteTransactionsForm = useForm({ data: [] as number[] });
const removeAllForm = useForm({});

// Total drafts in the inbox (may exceed what's loaded on the page).
const page = usePage();
const totalDrafts = computed(() => Number((page.props as any).pendingReviewCount) || 0);
const allInboxSelected = ref(false);

const fetchDrafts = () => {
    return axios
        .get(`/api/finance/transactions?filter[status]=draft&limit=200&relationships=linked`)
        .then(({ data }) => {
            drafts.value = data;
        })
        .finally(() => {
            isLoading.value = false;
        });
};

onMounted(fetchDrafts);

const refresh = () => {
    isLoading.value = true;
    fetchDrafts();
};

// Gmail-style selection. The list loads up to 200 drafts; "select all" ticks
// every loaded row, and when the inbox holds more than what's loaded the banner
// offers to escalate to the whole inbox — which deletes via the remove-all-drafts
// endpoint instead of by id.
const allSelected = computed(() => drafts.value.length > 0 && selected.value.length >= drafts.value.length);
const hasMoreThanLoaded = computed(() => totalDrafts.value > drafts.value.length);
const selectionActive = computed(() => selected.value.length > 0 || allInboxSelected.value);

const toggleSelectAll = () => {
    if (allSelected.value) { allInboxSelected.value = false; listRef.value?.clearSelection(); }
    else listRef.value?.selectAll();
};
const selectAllInbox = () => { allInboxSelected.value = true; };
const clearAll = () => { allInboxSelected.value = false; listRef.value?.clearSelection(); };

// The "all inbox" escalation only holds while the whole page stays selected.
watch(() => selected.value.length, (len) => {
    if (allInboxSelected.value && len < drafts.value.length) allInboxSelected.value = false;
});

const afterDelete = () => { clearAll(); fetchDrafts(); router.reload({ only: ['pendingReviewCount'] }); };

const deleteAllInbox = () => {
    if (!confirm(t('Delete all {n} items in your inbox? This cannot be undone.', { n: totalDrafts.value }))) return;
    removeAllForm.post('/transactions/remove-all-drafts', { preserveScroll: true, onSuccess: afterDelete });
};
const bulkDelete = () => {
    if (allInboxSelected.value) return deleteAllInbox();
    const ids = [...selected.value].map(Number).filter((n) => !Number.isNaN(n));
    if (!ids.length) return;
    if (!confirm(t('Delete {n} selected transactions? This cannot be undone.', { n: ids.length }))) return;
    deleteTransactionsForm.transform(() => ({ data: ids })).post('/finance/transactions/bulk/delete', {
        preserveScroll: true,
        onSuccess: afterDelete,
    });
};

// Approving opens the edit/confirm modal (same flow as the dashboard widget).
const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: any) => {
    axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
        openTransactionModal({ transactionData: data });
    });
};

// When a draft becomes verified or is removed, refetch the list AND refresh the
// shared pendingReviewCount so the sidebar badge stays in sync.
const transactionStore = useTransactionStore();
transactionStore.$onAction(({ name, args, after }) => {
    after(() => {
        const [savedValue, action, originalData] = args as any[];
        const confirmed = originalData && originalData.status === 'draft' && savedValue?.status === 'verified';
        if (confirmed || action === 'delete' || name === 'reload') {
            fetchDrafts();
            router.reload({ only: ['pendingReviewCount'] });
        }
    });
});

const hasItems = computed(() => drafts.value.length > 0);

// App currency (falls back to DOP). Passed per-row so amounts show the symbol.
const currency = (window as any).logerAppSettings?.currency_code || 'DOP';

// Inbox-specific parser: reads like a human — the payee as the title, the
// account as the secondary line — instead of the raw bank reference string.
const parseDrafts = (rows: any[]) => rows.map((t) => ({
    id: t.id,
    date: t.date,
    title: t.payee_name || t.description || '—',
    value: t.total,
    currencyCode: t.currency_code || currency,
    valueSubtitle: t.account_name || '',
    subtitle: t.category_name || '',
    expenses: 0,
    status: t.status,
}));

const captureMethods = [
    { icon: 'fa fa-camera', color: '#7B77D1', title: 'Snap a receipt', description: 'Take a photo and Claude reads the amount, date, and payee for you.' },
    { icon: 'fa fa-file-invoice', color: '#80CDFE', title: 'Forward a statement', description: 'Send a bank PDF and its transactions get drafted automatically.' },
    { icon: 'fa fa-pen', color: '#6EE7B7', title: 'Jot a quick note', description: 'Type or dictate anything — Claude sorts it into the right place.' },
];
</script>

<template>
    <AppLayout :title="$t('Inbox')">
        <main class="px-5 mx-auto pt-16 max-w-screen-2xl sm:px-6 lg:px-8 md:pr-16">
            <header class="flex items-start justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-body">{{ $t('Inbox') }}</h1>
                    <p class="mt-1 text-body-2">{{ $t('Everything you capture lands here first — Claude sorts it, you confirm.') }}</p>
                </div>
                <LogerButton
                    v-if="hasItems"
                    variant="inverse"
                    class="text-xs rounded-full"
                    :processing="isLoading"
                    :icon="MdiSync"
                    @click="refresh()"
                />
            </header>

            <!-- Loading -->
            <section v-if="isLoading && !drafts.length" class="py-16 text-sm text-center text-body-2">
                {{ $t('Loading…') }}
            </section>

            <!-- Draft review list -->
            <section v-else-if="hasItems" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="flex items-center gap-2 text-sm font-semibold tracking-wide uppercase text-body-2">
                        {{ $t('Needs your review') }}
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-primary/10 text-primary">
                            {{ $page.props.pendingReviewCount }}
                        </span>
                    </h3>
                    <label class="flex items-center gap-2 text-xs cursor-pointer select-none text-body-2">
                        <input type="checkbox" class="rounded border-base" :checked="allSelected" @change="toggleSelectAll" />
                        {{ $t('Select all') }}
                    </label>
                </div>

                <!-- Gmail-style selection banner: page vs whole-inbox -->
                <div
                    v-if="selectionActive"
                    class="flex flex-wrap items-center justify-center px-4 py-2.5 text-sm border rounded-lg gap-x-3 gap-y-1 bg-base-lvl-2 border-base text-body-2"
                >
                    <span v-if="allInboxSelected">
                        {{ $t('All {n} conversations in your inbox are selected.', { n: totalDrafts }) }}
                    </span>
                    <template v-else-if="allSelected">
                        <span>{{ $t('All {n} on this page are selected.', { n: selected.length }) }}</span>
                        <button
                            v-if="hasMoreThanLoaded"
                            type="button"
                            class="font-semibold text-primary hover:underline"
                            @click="selectAllInbox"
                        >{{ $t('Select all {n} in your inbox', { n: totalDrafts }) }}</button>
                    </template>
                    <span v-else>{{ $t('{n} selected', { n: selected.length }) }}</span>

                    <span class="opacity-30">·</span>
                    <button type="button" class="font-semibold text-error hover:underline" @click="bulkDelete">
                        {{ $t('Delete') }}
                    </button>
                    <button type="button" class="hover:underline" @click="clearAll">
                        {{ $t('Clear selection') }}
                    </button>
                </div>

                <TransactionsList
                    ref="listRef"
                    class="w-full"
                    table-class="w-full p-2 overflow-auto text-sm rounded-lg shadow-md bg-base-lvl-3"
                    v-model:selected="selected"
                    :transactions="drafts"
                    :parser="parseDrafts"
                    :allow-remove="true"
                    allow-match
                    :allow-mark-as-approved="true"
                    :hide-accounts="true"
                    @approved="handleEdit"
                    @removed="removeTransaction($event, ['draft'])"
                />

            </section>

            <!-- Inbox zero -->
            <template v-else>
                <section class="flex flex-col items-center justify-center max-w-xl py-12 mx-auto text-center">
                    <div class="flex items-center justify-center w-16 h-16 mb-5 rounded-full bg-success/10 text-success">
                        <i class="text-2xl fa fa-check"></i>
                    </div>
                    <h2 class="text-xl font-bold text-body">{{ $t("You're all caught up") }}</h2>
                    <p class="max-w-md mt-2 text-body-2">
                        {{ $t('Nothing to review right now. New receipts, statements, and notes will appear here for you to confirm.') }}
                    </p>
                </section>

                <section class="mt-4">
                    <h3 class="mb-4 text-sm font-semibold tracking-wide uppercase text-body-2">
                        {{ $t('How your inbox fills up') }}
                    </h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div v-for="method in captureMethods" :key="method.title" class="p-5 border rounded-lg bg-base-lvl-3 border-base">
                            <div class="flex items-center justify-center mb-4 rounded-full w-11 h-11" :style="{ backgroundColor: method.color + '1F', color: method.color }">
                                <i :class="method.icon"></i>
                            </div>
                            <h4 class="font-semibold text-body">{{ $t(method.title) }}</h4>
                            <p class="mt-1 text-sm text-body-2">{{ $t(method.description) }}</p>
                        </div>
                    </div>
                </section>
            </template>
        </main>
    </AppLayout>
</template>
