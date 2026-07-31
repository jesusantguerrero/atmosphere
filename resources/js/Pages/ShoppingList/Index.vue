<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import ShoppingChatList from '@/domains/shopping/components/ShoppingChatList.vue';

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
    lists?: ListSummary[];
    activeListId?: number;
    shareUrl: string | null;
    shareToken: string | null;
    mercureUrl: string | null;
}>();

const shareUrl = ref<string | null>(props.shareUrl);
const shareToken = ref<string | null>(props.shareToken);
const showShareSheet = ref(false);

// Computed so switching lists (which swaps props.plan) always retargets the
// mutation endpoints at the currently-active list.
const endpoints = computed(() => ({
    cycle: (itemId: number) => `/shopping/${props.plan.id}/items/${itemId}/cycle`,
    add: `/shopping/${props.plan.id}/items`,
    reset: `/shopping/${props.plan.id}/reset`,
    destroy: (itemId: number) => `/shopping/${props.plan.id}/items/${itemId}`,
}));

const switchList = (id: number) => {
    if (id === props.plan.id) return;
    router.visit(`/shopping?plan=${id}`);
};

const createList = async (name: string) => {
    const clean = name.trim();
    if (!clean) return;
    const { data } = await axios.post('/shopping/lists', { name: clean });
    router.visit(`/shopping?plan=${data.activeListId}`);
};

const renameList = async ({ id, name }: { id: number; name: string }) => {
    const clean = name.trim();
    if (!clean) return;
    await axios.put(`/shopping/${id}`, { name: clean });
    router.reload();
};

const deleteList = async (id: number) => {
    await axios.delete(`/shopping/${id}`);
    router.visit('/shopping');
};

const importList = async ({ name, text }: { name: string; text: string }) => {
    if (!text.trim()) return;
    const { data } = await axios.post('/shopping/import', { name, text });
    router.visit(`/shopping?plan=${data.activeListId}`);
};

const enableShare = async () => {
    const response = await axios.post(`/shopping/${props.plan.id}/share`);
    if (response.data.shared) {
        shareUrl.value = response.data.url;
        shareToken.value = response.data.token;
    } else {
        shareUrl.value = null;
        shareToken.value = null;
    }
};

const copyShareUrl = async () => {
    if (!shareUrl.value) return;
    try {
        await navigator.clipboard.writeText(shareUrl.value);
    } catch {
        // Clipboard might be blocked (insecure context) — ignore silently.
    }
};
</script>

<template>
    <AppLayout :title="$t('Shopping list')">
        <template #title>
            <h4 class="text-xs font-bold flex items-center gap-2 lg:ml-6">
                <IMdiCartOutline class="w-4 h-4" />
                {{ $t('Shopping list') }}
            </h4>
        </template>

        <ShoppingChatList
            :plan="plan"
            :lists="lists"
            :active-list-id="activeListId"
            api-base="/shopping"
            :mercure-url="mercureUrl"
            :show-owner-controls="true"
            :endpoints="endpoints"
            @switch="switchList"
            @create="createList"
            @rename="renameList"
            @delete="deleteList"
            @import="importList"
        >
            <template #header-actions>
                <button
                    type="button"
                    class="text-xs px-2 py-1 rounded-md bg-base-lvl-2 text-body-1 hover:bg-base-lvl-1 transition inline-flex items-center"
                    :title="shareUrl ? $t('Manage sharing') : $t('Share with someone')"
                    @click="showShareSheet = !showShareSheet"
                >
                    <IMdiShareVariant class="w-3.5 h-3.5 mr-1" />
                    {{ shareUrl ? $t('Sharing') : $t('Share') }}
                </button>
            </template>
        </ShoppingChatList>

        <Teleport to="body">
            <div
                v-if="showShareSheet"
                class="fixed inset-x-3 bottom-20 max-w-sm mx-auto bg-base-lvl-3 border border-base rounded-xl shadow-2xl p-4 z-30"
            >
                <h3 class="font-bold text-body mb-2">{{ $t('Share this list') }}</h3>
                <p v-if="!shareUrl" class="text-sm text-body-1/70 mb-3">
                    {{ $t('Anyone with the link can edit. Updates appear here in real time.') }}
                </p>
                <div v-else class="mb-3">
                    <p class="text-xs text-body-1/60 mb-1">{{ $t('Share link') }}</p>
                    <div class="flex items-center gap-2">
                        <input
                            :value="shareUrl"
                            readonly
                            class="flex-1 px-2 py-1.5 text-xs bg-base-lvl-2 border border-base rounded-md text-body"
                        />
                        <button
                            type="button"
                            class="text-xs px-2 py-1.5 rounded-md bg-primary text-white inline-flex items-center"
                            @click="copyShareUrl"
                        >
                            <IMdiContentCopy class="mr-1" />{{ $t('Copy') }}
                        </button>
                    </div>
                </div>
                <button
                    type="button"
                    class="w-full text-sm py-2 rounded-md font-semibold transition"
                    :class="shareUrl ? 'bg-error/10 text-error hover:bg-error/20' : 'bg-primary text-white'"
                    @click="enableShare"
                >
                    {{ shareUrl ? $t('Stop sharing') : $t('Enable share link') }}
                </button>
            </div>
        </Teleport>
    </AppLayout>
</template>
