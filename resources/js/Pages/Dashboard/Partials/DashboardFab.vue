<script setup lang="ts">
import { ref } from 'vue';
import { onClickOutside } from '@vueuse/core';

import { useTransactionModal, TRANSACTION_DIRECTIONS } from '@/domains/transactions';

const { openTransactionModal } = useTransactionModal();

const isExpanded = ref(false);
const fabRef = ref<HTMLElement | null>(null);

onClickOutside(fabRef, () => {
    isExpanded.value = false;
});

const toggle = () => {
    isExpanded.value = !isExpanded.value;
};

const openExpense = () => {
    isExpanded.value = false;
    openTransactionModal({ mode: TRANSACTION_DIRECTIONS.WITHDRAW });
};

const openIncome = () => {
    isExpanded.value = false;
    openTransactionModal({ mode: TRANSACTION_DIRECTIONS.DEPOSIT });
};

const openTransfer = () => {
    isExpanded.value = false;
    openTransactionModal({ mode: TRANSACTION_DIRECTIONS.TRANSFER });
};
</script>

<template>
    <div ref="fabRef" class="fixed bottom-20 right-6 z-50 flex flex-col items-end gap-3">
        <!-- Action items (expanded state) -->
        <transition
            enter-active-class="transition-all duration-200 ease-out"
            leave-active-class="transition-all duration-150 ease-in"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-2"
        >
            <div v-if="isExpanded" class="flex flex-col items-end gap-2">
                <button
                    class="flex items-center gap-2 bg-base-lvl-3 border border-base shadow-lg rounded-full px-4 py-2 text-sm font-semibold text-body-1 hover:bg-base-lvl-2 transition"
                    @click="openTransfer"
                >
                    <IMdiSwapHorizontal class="text-primary" />
                    {{ $t('Add Transfer') }}
                </button>
                <button
                    class="flex items-center gap-2 bg-base-lvl-3 border border-base shadow-lg rounded-full px-4 py-2 text-sm font-semibold text-body-1 hover:bg-base-lvl-2 transition"
                    @click="openIncome"
                >
                    <IMdiTrendingUp class="text-success" />
                    {{ $t('Add Income') }}
                </button>
                <button
                    class="flex items-center gap-2 bg-base-lvl-3 border border-base shadow-lg rounded-full px-4 py-2 text-sm font-semibold text-body-1 hover:bg-base-lvl-2 transition"
                    @click="openExpense"
                >
                    <IMdiTrendingDown class="text-error" />
                    {{ $t('Add Expense') }}
                </button>
            </div>
        </transition>

        <!-- Main FAB button -->
        <button
            class="w-14 h-14 rounded-full bg-primary text-white shadow-lg flex items-center justify-center transition-transform active:scale-95 hover:bg-primaryDark focus:outline-none"
            :class="{ 'rotate-45': isExpanded }"
            @click="toggle"
            :aria-label="$t('Quick actions')"
        >
            <IMdiPlus class="text-xl transition-transform" />
        </button>
    </div>
</template>
