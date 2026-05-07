<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

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

const props = defineProps<{
    plan: PlanPayload;
    shareUrl: string | null;
    shareToken: string | null;
    mercureUrl: string | null;
}>();

const shareUrl = ref<string | null>(props.shareUrl);
const shareToken = ref<string | null>(props.shareToken);
const showShareSheet = ref(false);

const endpoints = {
    cycle: (itemId: number) => `/shopping/${props.plan.id}/items/${itemId}/cycle`,
    add: `/shopping/${props.plan.id}/items`,
    reset: `/shopping/${props.plan.id}/reset`,
    destroy: (itemId: number) => `/shopping/${props.plan.id}/items/${itemId}`,
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
        <!-- The chat-style component owns the full mobile viewport; the share
             sheet floats above it as a small panel. -->
        <ShoppingChatList
            :plan="plan"
            api-base="/shopping"
            :mercure-url="mercureUrl"
            :show-owner-controls="true"
            :endpoints="endpoints"
        />

        <Teleport to="body">
            <button
                type="button"
                class="fixed bottom-20 right-4 w-12 h-12 flex items-center justify-center rounded-full bg-secondary text-white shadow-lg z-30"
                :title="shareUrl ? $t('Manage sharing') : $t('Share with someone')"
                @click="showShareSheet = !showShareSheet"
            >
                <i class="fa fa-share-nodes" />
            </button>

            <div
                v-if="showShareSheet"
                class="fixed inset-x-3 bottom-36 max-w-sm mx-auto bg-base-lvl-3 border border-base rounded-xl shadow-2xl p-4 z-30"
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
                            class="text-xs px-2 py-1.5 rounded-md bg-primary text-white"
                            @click="copyShareUrl"
                        >
                            <i class="fa fa-copy mr-1" />{{ $t('Copy') }}
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
