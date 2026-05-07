<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';

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
}>();

const stages = ref<Stage[]>(JSON.parse(JSON.stringify(props.plan.stages)));
const composer = ref<string>('');
const sending = ref(false);
const filter = ref<'all' | 'pending' | 'buy' | 'skip'>('all');

watch(
    () => props.plan,
    (next) => {
        stages.value = JSON.parse(JSON.stringify(next.stages));
    },
    { deep: true }
);

const allItems = computed<Item[]>(() => stages.value.flatMap((s) => s.items));

const visibleItems = computed<Item[]>(() => {
    if (filter.value === 'all') return allItems.value;
    return allItems.value.filter((i) => i.state === filter.value);
});

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
        await axios.post(props.endpoints.cycle(item.id), { state: next });
    } catch {
        applyItemUpdate({ id: item.id, state: previous, is_done: previous === 'buy' });
    }
};

const submitComposer = async () => {
    const title = composer.value.trim();
    if (!title || sending.value) return;

    sending.value = true;
    try {
        const response = await axios.post(props.endpoints.add, { title });
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
    if (!window.confirm(`Remove "${item.title}" from this list?`)) return;

    const snapshot = stages.value.map((s) => ({ ...s, items: [...s.items] }));
    removeItemLocally(item.id);

    try {
        await axios.delete(props.endpoints.destroy(item.id));
    } catch {
        stages.value = snapshot;
    }
};

const reset = async () => {
    if (!window.confirm('Reset every item to pending? This starts a fresh trip.')) return;
    await axios.post(props.endpoints.reset);
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

onMounted(wireMercure);
onBeforeUnmount(() => {
    eventSource?.close();
    eventSource = null;
});
</script>

<template>
    <div class="flex flex-col h-screen bg-base-lvl-1">
        <!-- Header: list name + filter chips + owner controls -->
        <header class="px-4 py-3 bg-base-lvl-3 border-b border-base">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-lg font-bold text-body truncate">{{ plan.name }}</h1>
                    <p class="text-xs text-body-1/60 mt-0.5">
                        {{ counts.buy }} buying · {{ counts.skip }} skipped · {{ counts.pending }} pending
                    </p>
                </div>
                <div v-if="showOwnerControls" class="flex items-center gap-2 shrink-0">
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
            <div class="flex gap-2 mt-3 overflow-x-auto -mx-1 px-1">
                <button
                    v-for="tab in (['all', 'pending', 'buy', 'skip'] as const)"
                    :key="tab"
                    type="button"
                    class="px-3 py-1 rounded-full text-xs font-semibold transition whitespace-nowrap border"
                    :class="filter === tab
                        ? 'bg-primary text-white border-primary'
                        : 'bg-base-lvl-2 text-body-1 border-base hover:bg-base-lvl-1'"
                    @click="filter = tab"
                >
                    {{ tab === 'all' ? $t('All') : tab === 'buy' ? $t('Buy') : tab === 'skip' ? $t('Skip') : $t('Pending') }}
                    <span v-if="tab !== 'all'" class="ml-1 opacity-70">{{ counts[tab] }}</span>
                </button>
            </div>
        </header>

        <!-- Item list — scrolls behind the sticky composer -->
        <main class="flex-1 overflow-y-auto px-3 py-3 space-y-2">
            <div
                v-if="visibleItems.length === 0"
                class="flex flex-col items-center justify-center text-center text-body-1/60 py-16 px-6"
            >
                <div class="text-4xl mb-2">🛒</div>
                <p class="text-sm">{{ $t('Nothing here yet.') }}</p>
                <p class="text-xs text-body-1/40 mt-1">
                    {{ $t('Type an item below and hit send.') }}
                </p>
            </div>

            <div
                v-for="item in visibleItems"
                :key="item.id"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl border transition cursor-pointer select-none"
                :class="stateClass(item.state)"
                @click="cycleItem(item)"
            >
                <i class="fa text-base shrink-0" :class="stateIcon(item.state)" />
                <span class="flex-1 text-sm break-words">{{ item.title }}</span>
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
        </main>

        <!-- Sticky composer at the bottom — WhatsApp-style. The keyboard pushes
             this up naturally on iOS/Android because it's flex-positioned, not
             fixed. -->
        <footer class="border-t border-base bg-base-lvl-3 px-3 py-2 pb-safe">
            <form class="flex items-center gap-2" @submit.prevent="submitComposer">
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
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: max(0.5rem, env(safe-area-inset-bottom));
}
</style>
