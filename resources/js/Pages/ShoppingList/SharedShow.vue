<script setup lang="ts">
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
    token: string;
    mercureUrl: string | null;
}>();

const endpoints = {
    cycle: (itemId: number) => `/shared/list/${props.token}/toggle/${itemId}`,
    add: `/shared/list/${props.token}/items`,
    reset: `/shared/list/${props.token}/reset`,
    // No destroy on the public view — partner shouldn't delete master items.
};
</script>

<template>
    <!-- No AppLayout — this page is no-auth and standalone, like the WatchlistShare
         public view. The chat component fills the viewport. -->
    <ShoppingChatList
        :plan="plan"
        :api-base="`/shared/list/${token}`"
        :mercure-url="mercureUrl"
        :show-owner-controls="false"
        :endpoints="endpoints"
    />
</template>
