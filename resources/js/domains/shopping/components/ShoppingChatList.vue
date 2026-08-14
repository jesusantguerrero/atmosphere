<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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

interface ListSummary {
    id: number;
    name: string;
    total: number;
    pending: number;
    shared: boolean;
}

const props = defineProps<{
    plan: PlanPayload;
    /** Endpoint base for mutations. Authed view uses `/shopping/{plan}`,
     *  public view uses `/shared/list/{token}`. */
    apiBase: string;
    /** Mercure subscribe URL keyed by share-token, when sharing is on. */
    mercureUrl?: string | null;
    /** When true, renders the share / reset / settings buttons in the header. */
    showOwnerControls?: boolean;
    /** Endpoint paths differ slightly between authed/shared views. */
    endpoints: {
        cycle: (itemId: number) => string;
        add: string;
        reset: string;
        destroy?: (itemId: number) => string;
    };
    /** Owner-only: all lists for the header switcher. Absent in the shared view. */
    lists?: ListSummary[];
    activeListId?: number;
}>();

const emit = defineEmits<{
    (e: 'switch', id: number): void;
    (e: 'create', name: string): void;
    (e: 'rename', payload: { id: number; name: string }): void;
    (e: 'delete', id: number): void;
    (e: 'import', payload: { name: string; text: string }): void;
}>();

const showListMenu = ref(false);
const showImport = ref(false);
const importName = ref('');
const importText = ref('');

const chooseList = (id: number) => {
    showListMenu.value = false;
    emit('switch', id);
};
const promptCreate = () => {
    showListMenu.value = false;
    const name = window.prompt('Name of the new list?');
    if (name && name.trim()) emit('create', name.trim());
};
const promptRename = () => {
    showListMenu.value = false;
    const name = window.prompt('Rename list', props.plan.name);
    if (name && name.trim()) emit('rename', { id: props.plan.id, name: name.trim() });
};
const confirmDelete = () => {
    showListMenu.value = false;
    if (window.confirm(t('Delete "{name}" and all its items?', { name: props.plan.name }))) emit('delete', props.plan.id);
};
const openImport = () => {
    showListMenu.value = false;
    importName.value = '';
    importText.value = '';
    showImport.value = true;
};
const submitImport = () => {
    if (!importText.value.trim()) return;
    emit('import', { name: importName.value.trim(), text: importText.value });
    showImport.value = false;
};

const stages = ref<Stage[]>(JSON.parse(JSON.stringify(props.plan.stages)));
const composer = ref<string>('');
const sending = ref(false);

watch(
    () => props.plan,
    (next) => {
        stages.value = JSON.parse(JSON.stringify(next.stages));
    },
    { deep: true }
);

const allItems = computed<Item[]>(() => stages.value.flatMap((s) => s.items));

// LM-9: every state stays visible until "reset trip" — supermarket flow is
// mark-while-you-shop, confirm-at-the-end. Items group: pending (still to buy)
// → buy (already in cart) → skip (not getting today). The previous filter tabs
// hard-removed items on tap, which felt like the row was being deleted.
const STATE_ORDER = { pending: 0, buy: 1, skip: 2 } as const;

// The default list's raw name is a system constant (e.g. SHOPPING_LIST); show a
// friendly translated title instead — this is the only screen a shared guest sees.
const listTitle = computed(() => {
    const n = (props.plan.name ?? '').trim();
    return /^[A-Z][A-Z0-9_]+$/.test(n) ? t('Shopping list') : n;
});
const visibleItems = computed<Item[]>(() =>
    [...allItems.value].sort((a, b) => {
        const sa = STATE_ORDER[a.state];
        const sb = STATE_ORDER[b.state];
        if (sa !== sb) return sa - sb;
        return a.order - b.order;
    })
);

// Categories = stages. Section headers show only when >1 stage; a single
// default stage renders as a flat list (unchanged from before).
const hasCategories = computed(() => stages.value.length > 1);
const visibleSections = computed(() =>
    stages.value.map((s) => ({
        id: s.id,
        name: s.name,
        items: [...s.items].sort((a, b) => {
            const sa = STATE_ORDER[a.state];
            const sb = STATE_ORDER[b.state];
            if (sa !== sb) return sa - sb;
            return a.order - b.order;
        }),
    }))
);

const counts = computed(() => ({
    total: allItems.value.length,
    pending: allItems.value.filter((i) => i.state === 'pending').length,
    buy: allItems.value.filter((i) => i.state === 'buy').length,
    skip: allItems.value.filter((i) => i.state === 'skip').length,
}));

const findItem = (id: number) => allItems.value.find((i) => i.id === id);

const applyItemUpdate = (next: Partial<Item> & { id: number }) => {
    for (const stage of stages.value) {
        const idx = stage.items.findIndex((i) => i.id === next.id);
        if (idx !== -1) {
            stage.items[idx] = { ...stage.items[idx], ...next };
            return;
        }
    }
};

const removeItemLocally = (id: number) => {
    for (const stage of stages.value) {
        stage.items = stage.items.filter((i) => i.id !== id);
    }
};

const addItemLocally = (item: Item) => {
    if (findItem(item.id)) return;
    if (stages.value[0]) stages.value[0].items.push(item);
};

const cycleItem = async (item: Item) => {
    const previous = item.state;
    const next: Item['state'] =
        previous === 'pending' ? 'buy' : previous === 'buy' ? 'skip' : 'pending';

    applyItemUpdate({ id: item.id, state: next, is_done: next === 'buy' });

    try {
        await axios.post(props.endpoints.cycle(item.id), { state: next }, requestConfig());
    } catch {
        applyItemUpdate({ id: item.id, state: previous, is_done: previous === 'buy' });
    }
};

const submitComposer = async () => {
    const title = composer.value.trim();
    if (!title || sending.value) return;

    sending.value = true;
    try {
        const response = await axios.post(props.endpoints.add, { title }, requestConfig());
        const created: Item = {
            id: response.data.id,
            title: response.data.title,
            state: response.data.state ?? 'pending',
            is_done: response.data.state === 'buy',
            order: response.data.order ?? allItems.value.length + 1,
        };
        addItemLocally(created);
        composer.value = '';
    } finally {
        sending.value = false;
    }
};

const removeItem = async (item: Item) => {
    if (!props.endpoints.destroy) return;
    if (!window.confirm(t('Remove "{title}" from this list?', { title: item.title }))) return;

    const snapshot = stages.value.map((s) => ({ ...s, items: [...s.items] }));
    removeItemLocally(item.id);

    try {
        await axios.delete(props.endpoints.destroy(item.id), requestConfig());
    } catch {
        stages.value = snapshot;
    }
};

const reset = async () => {
    if (!window.confirm(t('Reset every item to pending? This starts a fresh trip.'))) return;
    await axios.post(props.endpoints.reset, {}, requestConfig());
    for (const stage of stages.value) {
        for (const item of stage.items) {
            item.state = 'pending';
            item.is_done = false;
        }
    }
};

const stateClass = (state: Item['state']) => {
    if (state === 'buy') return 'bg-success/10 text-body border-success/40';
    if (state === 'skip') return 'bg-error/5 text-body-1/50 border-error/30 line-through';
    return 'bg-base-lvl-2 text-body border-base';
};

const stateIcon = (state: Item['state']) => {
    if (state === 'buy') return 'fa-check text-success';
    if (state === 'skip') return 'fa-xmark text-error';
    return 'fa-circle text-body-1/30';
};

// Tag the OneSignal subscription with this list's share token so the backend
// listener can target push notifications to everyone viewing this list. We
// only tag when sharing is enabled (i.e. there's a token to address). The
// global OneSignal SDK is loaded by app.blade.php and exposed on `window`.
const oneSignalTagKey = 'shopping_list';
const tagToken = computed<string | null>(() => {
    if (!props.mercureUrl) return null;
    const match = props.mercureUrl.match(/\/shared\/list\/([^?&/]+)/);
    return match ? match[1] : null;
});

declare global {
    interface Window {
        OneSignalDeferred?: Array<(os: any) => Promise<void> | void>;
    }
}

// We capture this device's OneSignal subscription ID so the server can pass
// it to OneSignal as `excluded_subscription_ids` when broadcasting our own
// changes — otherwise we'd echo a push back to the device that just made the
// change. Stored in a ref so axios picks up changes when the SDK resolves.
const oneSignalSubscriptionId = ref<string | null>(null);

const captureOneSignalSubscriptionId = () => {
    if (typeof window === 'undefined' || !window.OneSignalDeferred) return;
    window.OneSignalDeferred.push(async (OneSignal: any) => {
        try {
            const id = OneSignal?.User?.PushSubscription?.id;
            if (id) oneSignalSubscriptionId.value = String(id);
            // The id may not be available right away (subscription not opted-in
            // yet). Listen for the `change` event so we capture it on opt-in.
            OneSignal?.User?.PushSubscription?.addEventListener?.('change', (event: any) => {
                const next = event?.current?.id ?? OneSignal?.User?.PushSubscription?.id ?? null;
                oneSignalSubscriptionId.value = next ? String(next) : null;
            });
        } catch {
            // Silent — exclusion is best-effort.
        }
    });
};

const tagOneSignal = (token: string) => {
    if (typeof window === 'undefined' || !window.OneSignalDeferred) return;
    window.OneSignalDeferred.push(async (OneSignal: any) => {
        try {
            await OneSignal.User.addTag(oneSignalTagKey, token);
        } catch {
            // Silent — push is best-effort; nothing should break shopping.
        }
    });
};

const untagOneSignal = () => {
    if (typeof window === 'undefined' || !window.OneSignalDeferred) return;
    window.OneSignalDeferred.push(async (OneSignal: any) => {
        try {
            await OneSignal.User.removeTag(oneSignalTagKey);
        } catch {
            // Silent — see above.
        }
    });
};

/** Adds the subscription-ID exclusion header so the server can suppress an
 *  echo push back to this device. Returns config object for axios. */
const requestConfig = () => {
    const headers: Record<string, string> = {};
    if (oneSignalSubscriptionId.value) {
        headers['X-OneSignal-Subscription-Id'] = oneSignalSubscriptionId.value;
    }
    return { headers };
};

// Mercure subscription — re-applies updates from other tabs/devices in real time.
let eventSource: EventSource | null = null;

const wireMercure = () => {
    if (!props.mercureUrl || typeof window === 'undefined' || typeof EventSource === 'undefined') return;

    eventSource = new EventSource(props.mercureUrl);
    eventSource.onmessage = (event) => {
        try {
            const payload = JSON.parse(event.data);
            if (payload.action === 'reset') {
                for (const stage of stages.value) {
                    for (const item of stage.items) {
                        item.state = 'pending';
                        item.is_done = false;
                    }
                }
                return;
            }
            if (payload.action === 'deleted' && payload.item) {
                removeItemLocally(payload.item.id);
                return;
            }
            if (payload.action === 'created' && payload.item) {
                addItemLocally({
                    id: payload.item.id,
                    title: payload.item.title,
                    state: payload.item.state ?? 'pending',
                    is_done: payload.item.is_done ?? false,
                    order: payload.item.order ?? 0,
                });
                return;
            }
            if (payload.action === 'updated' && payload.item) {
                applyItemUpdate({
                    id: payload.item.id,
                    state: payload.item.state,
                    is_done: payload.item.is_done,
                    title: payload.item.title,
                });
            }
        } catch {
            // Ignore malformed payloads — never break UI on a bad event.
        }
    };
    eventSource.onerror = () => {
        // Mercure hub may not be running (dev). Silently degrade: the
        // page still works, just without push updates from other tabs.
    };
};

onMounted(() => {
    wireMercure();
    captureOneSignalSubscriptionId();
    if (tagToken.value) tagOneSignal(tagToken.value);
});

watch(tagToken, (next, prev) => {
    if (next && next !== prev) tagOneSignal(next);
    if (!next && prev) untagOneSignal();
});

onBeforeUnmount(() => {
    eventSource?.close();
    eventSource = null;
    untagOneSignal();
});
</script>

<template>
    <!-- Pin to the available viewport area between AppLayout's top header
         (h-16 = 64px) and the mobile bottom-nav (h-14 = 56px, hidden on lg).
         Using fixed positioning so the chat composer at the bottom of this
         flex column stays visible without the user having to scroll past
         the AppShell chrome to reach it. -->
    <div class="fixed inset-x-0 top-16 bottom-14 lg:bottom-0 flex flex-col bg-base-lvl-1 z-10">
        <!-- Header: list name + filter chips + owner controls -->
        <header class="bg-base-lvl-3 border-b border-base">
            <div class="max-w-5xl mx-auto px-4 py-3 w-full">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 relative">
                        <button
                            v-if="showOwnerControls && lists && lists.length"
                            type="button"
                            class="flex items-center gap-1.5 text-lg font-bold text-body max-w-full"
                            @click="showListMenu = !showListMenu"
                        >
                            <span class="truncate">{{ listTitle }}</span>
                            <i class="fa fa-chevron-down text-xs text-body-1/60 shrink-0" />
                        </button>
                        <h1 v-else class="text-lg font-bold text-body truncate">{{ listTitle }}</h1>
                        <p class="text-xs text-body-1/60 mt-0.5">
                            {{ $t('{buy} buying · {skip} skipped · {pending} pending', { buy: counts.buy, skip: counts.skip, pending: counts.pending }) }}
                        </p>

                        <template v-if="showListMenu">
                            <div class="fixed inset-0 z-20" @click="showListMenu = false" />
                            <div class="absolute left-0 top-9 z-30 w-64 max-w-[80vw] bg-base-lvl-3 border border-base rounded-xl shadow-2xl py-1.5">
                                <button
                                    v-for="l in lists"
                                    :key="l.id"
                                    type="button"
                                    class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-left hover:bg-base-lvl-2 transition"
                                    :class="l.id === plan.id ? 'text-primary font-semibold' : 'text-body'"
                                    @click="chooseList(l.id)"
                                >
                                    <span class="truncate flex items-center gap-1.5">
                                        <i v-if="l.shared" class="fa fa-share-nodes text-[10px] text-body-1/50" />
                                        {{ l.name }}
                                    </span>
                                    <span class="text-[11px] text-body-1/50 shrink-0">{{ l.pending }}</span>
                                </button>
                                <div class="my-1 border-t border-base" />
                                <button type="button" class="w-full text-left px-3 py-2 text-sm text-body hover:bg-base-lvl-2 transition" @click="promptCreate">
                                    <i class="fa fa-plus w-4 text-body-1/60" /> {{ $t('New list') }}
                                </button>
                                <button type="button" class="w-full text-left px-3 py-2 text-sm text-body hover:bg-base-lvl-2 transition" @click="openImport">
                                    <i class="fa fa-paste w-4 text-body-1/60" /> {{ $t('Import from text') }}
                                </button>
                                <button type="button" class="w-full text-left px-3 py-2 text-sm text-body hover:bg-base-lvl-2 transition" @click="promptRename">
                                    <i class="fa fa-pen w-4 text-body-1/60" /> {{ $t('Rename this list') }}
                                </button>
                                <button type="button" class="w-full text-left px-3 py-2 text-sm text-error hover:bg-error/10 transition" @click="confirmDelete">
                                    <i class="fa fa-trash w-4" /> {{ $t('Delete this list') }}
                                </button>
                            </div>
                        </template>
                    </div>
                    <div v-if="showOwnerControls" class="flex items-center gap-2 shrink-0">
                        <!-- Page-level secondary actions (e.g. Share) inject here -->
                        <slot name="header-actions" />
                        <button
                            type="button"
                            class="text-xs px-2 py-1 rounded-md bg-base-lvl-2 text-body-1 hover:bg-base-lvl-1 transition"
                            :title="$t('Reset all items to pending')"
                            @click="reset"
                        >
                            <i class="fa fa-rotate-left mr-1" />
                            {{ $t('Reset') }}
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Item list — scrolls behind the sticky composer -->
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-5xl mx-auto px-3 py-3 w-full space-y-5">
                <div
                    v-if="allItems.length === 0"
                    class="flex flex-col items-center justify-center text-center text-body-1/60 py-16 px-6"
                >
                    <div class="text-4xl mb-2">🛒</div>
                    <p class="text-sm">{{ $t('Nothing here yet.') }}</p>
                    <p class="text-xs text-body-1/40 mt-1">
                        {{ $t('Type an item below and hit send.') }}
                    </p>
                </div>

                <section
                    v-for="section in visibleSections"
                    :key="section.id"
                    v-show="section.items.length || hasCategories"
                >
                    <h3
                        v-if="hasCategories"
                        class="text-[11px] font-bold uppercase tracking-wide text-body-1/60 px-1 mb-2"
                    >
                        {{ section.name }}
                    </h3>
                    <div class="space-y-2 md:space-y-0 md:grid md:grid-cols-2 xl:grid-cols-3 md:gap-2">
                        <div
                            v-for="item in section.items"
                            :key="item.id"
                            class="flex items-center gap-3 px-3 py-2.5 rounded-xl border transition cursor-pointer select-none"
                            :class="stateClass(item.state)"
                            @click="cycleItem(item)"
                        >
                            <i class="fa text-base shrink-0" :class="stateIcon(item.state)" />
                            <span class="flex-1 text-sm break-words">{{ item.title }}</span>
                            <span
                                v-if="item.state === 'buy'"
                                class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide bg-success/20 text-success"
                            >
                                {{ $t('Buying') }}
                            </span>
                            <span
                                v-else-if="item.state === 'skip'"
                                class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide bg-error/15 text-error/70"
                            >
                                {{ $t('Skip') }}
                            </span>
                            <button
                                v-if="endpoints.destroy"
                                type="button"
                                class="text-body-1/30 hover:text-error transition shrink-0 px-1"
                                :title="$t('Remove item')"
                                @click.stop="removeItem(item)"
                            >
                                <i class="fa fa-trash text-xs" />
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </main>

        <!-- Sticky composer at the bottom — WhatsApp-style. The keyboard pushes
             this up naturally on iOS/Android because it's flex-positioned, not
             fixed. -->
        <footer class="border-t border-base bg-base-lvl-3 pb-safe">
            <form
                class="max-w-5xl mx-auto px-3 py-2 flex items-center gap-2 w-full"
                @submit.prevent="submitComposer"
            >
                <input
                    v-model="composer"
                    type="text"
                    :placeholder="$t('Add an item…')"
                    class="flex-1 px-3 py-2.5 text-sm rounded-full bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    autocomplete="off"
                    enterkeyhint="send"
                />
                <button
                    type="submit"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-primary text-white shadow-md transition disabled:opacity-50"
                    :disabled="!composer.trim() || sending"
                    :title="$t('Send')"
                >
                    <i class="fa fa-paper-plane text-sm" />
                </button>
            </form>
        </footer>

        <Teleport to="body">
            <div
                v-if="showImport"
                class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/50"
                @click.self="showImport = false"
            >
                <div class="w-full max-w-lg bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-4">
                    <h3 class="font-bold text-body mb-1">{{ $t('Import a list') }}</h3>
                    <p class="text-xs text-body-1/60 mb-3">
                        {{ $t('Paste a list. Lines starting with a dash are items; other lines become categories.') }}
                    </p>
                    <input
                        v-model="importName"
                        type="text"
                        :placeholder="$t('List name (optional)')"
                        class="w-full mb-2 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    />
                    <textarea
                        v-model="importText"
                        rows="10"
                        placeholder="🛒 Compra para Diana&#10;🥩 Carnes&#10;- Pechuga cruda&#10;- Huevo"
                        class="w-full px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body focus:border-primary focus:outline-none"
                    />
                    <div class="flex items-center justify-end gap-2 mt-3">
                        <button
                            type="button"
                            class="text-sm px-3 py-2 rounded-lg text-body-1 hover:bg-base-lvl-2 transition"
                            @click="showImport = false"
                        >
                            {{ $t('Cancel') }}
                        </button>
                        <button
                            type="button"
                            class="text-sm px-4 py-2 rounded-lg font-semibold bg-primary text-white disabled:opacity-50 transition"
                            :disabled="!importText.trim()"
                            @click="submitImport"
                        >
                            {{ $t('Import') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
}
</style>
