<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import MdiShareVariant from '~icons/mdi/share-variant';
import MdiContentCopy from '~icons/mdi/content-copy';

interface Item {
    id: number;
    title: string;
    state: 'pending' | 'buy' | 'skip';
    is_done: boolean;
    order: number;
}

interface Stage {
    id: number;
    name: string;
    items: Item[];
}

interface PlanPayload {
    id: number;
    name: string;
    stages: Stage[];
}

const plan = ref<PlanPayload | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const composer = ref('');
const sending = ref(false);

const shareUrl = ref<string | null>(null);
const showShareSheet = ref(false);
const togglingShare = ref(false);
const copied = ref(false);

const allItems = computed<Item[]>(() => plan.value?.stages.flatMap((s) => s.items) ?? []);
const pendingItems = computed<Item[]>(() => allItems.value.filter((i) => i.state === 'pending'));
const buyItems = computed<Item[]>(() => allItems.value.filter((i) => i.state === 'buy'));
const skipItems = computed<Item[]>(() => allItems.value.filter((i) => i.state === 'skip'));

// LM-9: every state stays visible until "reset trip" — the supermarket flow is
// mark-while-you-shop, confirm-at-the-end. Order: pending first (still to buy),
// then buying (already in cart), then skipped (not getting today).
const visibleItems = computed<Item[]>(() => [
    ...pendingItems.value,
    ...buyItems.value,
    ...skipItems.value,
]);

// Group by stage (category); headers show only when the list has >1 stage.
const STATE_ORDER = { pending: 0, buy: 1, skip: 2 } as const;
const hasCategories = computed<boolean>(() => (plan.value?.stages.length ?? 0) > 1);
const visibleSections = computed(() =>
    (plan.value?.stages ?? []).map((s) => ({
        id: s.id,
        name: s.name,
        items: [...s.items].sort((a, b) => STATE_ORDER[a.state] - STATE_ORDER[b.state]),
    }))
);

const fetchPlan = async (): Promise<void> => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/shopping/current');
        plan.value = data.plan;
        shareUrl.value = data.shareUrl ?? null;
    } catch {
        error.value = 'Could not load your shopping list.';
    } finally {
        loading.value = false;
    }
};

const applyItemUpdate = (next: Partial<Item> & { id: number }): void => {
    if (!plan.value) return;
    for (const stage of plan.value.stages) {
        const idx = stage.items.findIndex((i) => i.id === next.id);
        if (idx !== -1) {
            stage.items[idx] = { ...stage.items[idx], ...next };
            return;
        }
    }
};

const cycleItem = async (item: Item): Promise<void> => {
    if (!plan.value) return;
    const previous = item.state;
    const next: Item['state'] =
        previous === 'pending' ? 'buy' : previous === 'buy' ? 'skip' : 'pending';

    applyItemUpdate({ id: item.id, state: next, is_done: next === 'buy' });

    try {
        await axios.post(`/shopping/${plan.value.id}/items/${item.id}/cycle`, { state: next });
    } catch {
        applyItemUpdate({ id: item.id, state: previous, is_done: previous === 'buy' });
    }
};

const submitComposer = async (): Promise<void> => {
    if (!plan.value) return;
    const title = composer.value.trim();
    if (!title || sending.value) return;

    sending.value = true;
    try {
        const { data } = await axios.post(`/shopping/${plan.value.id}/items`, { title });
        const created: Item = {
            id: data.id,
            title: data.title,
            state: data.state ?? 'pending',
            is_done: data.state === 'buy',
            order: data.order ?? allItems.value.length + 1,
        };
        const stage = plan.value.stages[0];
        if (stage) stage.items.push(created);
        composer.value = '';
    } finally {
        sending.value = false;
    }
};

const toggleShare = async (): Promise<void> => {
    if (!plan.value || togglingShare.value) return;
    togglingShare.value = true;
    try {
        const { data } = await axios.post(`/shopping/${plan.value.id}/share`);
        shareUrl.value = data.shared ? data.url : null;
    } finally {
        togglingShare.value = false;
    }
};

const copyShareUrl = async (): Promise<void> => {
    if (!shareUrl.value) return;
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch {
        // Clipboard blocked (insecure context) — silently degrade.
    }
};

const stateClass = (state: Item['state']): string => {
    if (state === 'buy') return 'bg-success/10 border-success/30 text-body';
    if (state === 'skip') return 'bg-error/5 border-error/20 text-body-1/50 line-through';
    return 'bg-base-lvl-2 border-base text-body';
};

const stateIcon = (state: Item['state']): string => {
    if (state === 'buy') return 'fa-check text-success';
    if (state === 'skip') return 'fa-xmark text-error';
    return 'fa-circle text-body-1/30';
};

onMounted(fetchPlan);
</script>

<template>
    <main class="flex flex-col h-full min-h-0">
        <!-- Counts + share toggle -->
        <header v-if="plan && !loading" class="flex items-center justify-between gap-2 mb-2 shrink-0">
            <div class="text-xs text-body-1/60">
                <span class="font-semibold text-body">{{ pendingItems.length }}</span> {{ $t('pending') }}
                <span class="mx-1.5 text-body-1/30">·</span>
                <span class="font-semibold text-success">{{ buyItems.length }}</span> {{ $t('buying') }}
                <template v-if="skipItems.length">
                    <span class="mx-1.5 text-body-1/30">·</span>
                    <span class="font-semibold text-body-1/50">{{ skipItems.length }}</span> {{ $t('skipped') }}
                </template>
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center h-7 w-7 rounded-md text-body-1/60 hover:bg-base-lvl-2 hover:text-primary transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                :class="{ 'bg-primary/10 text-primary': showShareSheet || shareUrl }"
                :title="shareUrl ? $t('Manage sharing') : $t('Share this list')"
                :aria-label="shareUrl ? $t('Manage sharing') : $t('Share this list')"
                @click="showShareSheet = !showShareSheet"
            >
                <MdiShareVariant class="h-4 w-4" />
            </button>
        </header>

        <!-- Inline share sheet — shows when the share button is toggled -->
        <section
            v-if="showShareSheet && plan && !loading"
            class="mb-3 p-3 rounded-lg bg-base-lvl-2 border border-base text-xs space-y-2 shrink-0"
        >
            <div v-if="shareUrl" class="space-y-2">
                <p class="text-body-1/70">{{ $t('Anyone with this link can edit. Updates appear in real time.') }}</p>
                <div class="flex items-center gap-1.5">
                    <input
                        :value="shareUrl"
                        readonly
                        class="flex-1 px-2 py-1.5 text-[11px] bg-base-lvl-3 border border-base rounded-md text-body min-w-0"
                        @focus="($event.target as HTMLInputElement).select()"
                    />
                    <button
                        type="button"
                        class="inline-flex items-center justify-center h-7 w-7 rounded-md bg-primary text-white shrink-0"
                        :title="$t('Copy link')"
                        :aria-label="$t('Copy link')"
                        @click="copyShareUrl"
                    >
                        <MdiContentCopy class="h-3.5 w-3.5" />
                    </button>
                </div>
                <div class="flex items-center justify-between gap-2 pt-1">
                    <span v-if="copied" class="text-[11px] text-success">{{ $t('Copied!') }}</span>
                    <span v-else class="text-[11px] text-body-1/40">&nbsp;</span>
                    <button
                        type="button"
                        class="text-[11px] text-error hover:underline"
                        :disabled="togglingShare"
                        @click="toggleShare"
                    >
                        {{ $t('Stop sharing') }}
                    </button>
                </div>
            </div>
            <div v-else>
                <p class="text-body-1/70 mb-2">{{ $t('Get a link your partner can open to see and edit this list.') }}</p>
                <button
                    type="button"
                    class="w-full text-xs py-2 rounded-md bg-primary text-white font-semibold disabled:opacity-50"
                    :disabled="togglingShare"
                    @click="toggleShare"
                >
                    {{ togglingShare ? $t('Enabling…') : $t('Enable share link') }}
                </button>
            </div>
        </section>

        <!-- List body -->
        <section class="flex-1 min-h-0 overflow-y-auto -mx-1 px-1 space-y-1.5">
            <div v-if="loading" class="text-xs text-body-1/50 py-6 text-center">
                {{ $t('Loading…') }}
            </div>

            <div v-else-if="error" class="text-xs text-error py-6 text-center">{{ error }}</div>

            <div
                v-else-if="!allItems.length"
                class="text-center text-body-1/60 py-8 px-4"
            >
                <div class="text-3xl mb-1">🛒</div>
                <p class="text-xs">{{ $t('Nothing on the list yet.') }}</p>
                <p class="text-[11px] text-body-1/40 mt-0.5">{{ $t('Type below to add an item.') }}</p>
            </div>

            <template v-else>
                <div
                    v-for="section in visibleSections"
                    :key="section.id"
                    v-show="section.items.length"
                    class="space-y-1.5"
                >
                    <p
                        v-if="hasCategories"
                        class="text-[11px] font-bold uppercase tracking-wide text-body-1/50 px-1 pt-1"
                    >
                        {{ section.name }}
                    </p>
                    <div
                        v-for="item in section.items"
                        :key="item.id"
                        class="flex items-center gap-2 px-2.5 py-2 rounded-lg border text-sm cursor-pointer select-none"
                        :class="stateClass(item.state)"
                        @click="cycleItem(item)"
                    >
                        <i class="fa text-xs shrink-0" :class="stateIcon(item.state)" />
                        <span class="flex-1 break-words text-xs">{{ item.title }}</span>
                        <span
                            v-if="item.state === 'buy'"
                            class="shrink-0 px-1.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-success/20 text-success"
                        >
                            {{ $t('Buying') }}
                        </span>
                        <span
                            v-else-if="item.state === 'skip'"
                            class="shrink-0 px-1.5 py-0.5 rounded-full text-[11px] font-semibold uppercase tracking-wide bg-error/15 text-error/70"
                        >
                            {{ $t('Skip') }}
                        </span>
                    </div>
                </div>
            </template>
        </section>

        <!-- Composer + open-full-list -->
        <footer v-if="!loading && !error" class="pt-3 mt-3 border-t border-base space-y-2 shrink-0">
            <form class="flex items-center gap-2" @submit.prevent="submitComposer">
                <input
                    v-model="composer"
                    type="text"
                    :placeholder="$t('Add an item…')"
                    class="flex-1 px-3 py-2 text-xs rounded-full bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    autocomplete="off"
                    enterkeyhint="send"
                />
                <button
                    type="submit"
                    class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white shadow transition disabled:opacity-50"
                    :disabled="!composer.trim() || sending"
                    :title="$t('Send')"
                >
                    <i class="fa fa-paper-plane text-[11px]" />
                </button>
            </form>

            <Link
                href="/shopping"
                class="block text-center text-xs text-primary hover:underline"
            >
                {{ $t('Open full list') }} →
            </Link>
        </footer>
    </main>
</template>
