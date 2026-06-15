<script setup lang="ts">
import { computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import MoreOptionsModal from "../MoreOptionsModal.vue";
import TransactionModal from "@/domains/transactions/components/TransactionModal.vue";
import PaymentFormModal from "@/domains/transactions/components/PaymentFormModal.vue";
import ImportResourceModal from "@/Components/ImportResourceModal.vue";

import { useTransactionModal, transactionModalState } from "@/domains/transactions";
import { useImportModal } from "@/domains/transactions/useImportModal";
import { useAppContextStore } from "@/store";
import { config } from "@/config/index";
import {
    usePaymentModal,
    modalState as paymentModalState,
} from "@/domains/transactions/usePaymentModal";
import { useToggleModal } from "@/domains/app/useToggleModal";
import OccurrenceCheckModal from "../OccurrenceCheckModal.vue";

const { isOpen, closeTransactionModal } = useTransactionModal();
const onTransactionSaved = () => {
    closeTransactionModal();
    router.reload();
};
const context = useAppContextStore();
const modalMaxWidth = computed(() => {
    return context.isMobile ? "mobile" : null;
});

// Transaction modal needs more horizontal room than the default 2xl: Date + Description
// + Currency in one row + Source/Destination/Amount/Tags in the body all benefit from
// breathing room. 4xl (~896px) is the sweet spot.
const transactionModalMaxWidth = computed(() => {
    return context.isMobile ? "mobile" : "4xl";
});

const { isOpen: isImportModalOpen } = useImportModal();

/**
 * TransactionModal lives at the app root and is opened from many places
 * via the useTransactionModal composable. None of those call sites have the
 * accounts list in scope, so we forward the globally-shared `accounts` Inertia
 * prop here. Without this the modal can't tell which accounts are
 * multi-currency, so the Currency picker stays hidden and editing a tx that's
 * not in the team default currency gives no visual cue about its currency.
 */
const page = usePage();
const sharedAccounts = computed(() => (page.props as any).accounts ?? []);

if (config.MERCURE_URL) {
    const url = new URL(config.MERCURE_URL);
    url.searchParams.append("topic", "https://example.com/main");
    url.searchParams.append("topic", "https://example.com/users/jesus");
    var es = new EventSource(url);
    es.onmessage = (messageEvent) => {
        var eventData = JSON.parse(messageEvent.data);
    };
}

const { isOpen: isPaymentModalOpen } = usePaymentModal();

//  contact form management
const {
    isOpen: isOccurrenceModalOpen,
    closeModal: closeOccurrenceModal,
    data: occurrenceData,
} = useToggleModal("occurrence");
</script>

<template>
    <TransactionModal v-model:show="isOpen" v-bind="transactionModalState" :accounts="sharedAccounts"
        :max-width="transactionModalMaxWidth" :full-height="context.isMobile"
        @saved="onTransactionSaved" @close="onTransactionSaved" />

    <MoreOptionsModal v-model:show="context.isMoreOptionsModalOpen" :max-width="modalMaxWidth"
        v-if="context.isMobile" />

    <PaymentFormModal v-if="paymentModalState" v-bind="paymentModalState?.data" v-model="isPaymentModalOpen"
        @saved="onTransactionSaved" />

    <ImportResourceModal v-model:show="isImportModalOpen" />


    <OccurrenceCheckModal v-model:show="isOccurrenceModalOpen" :max-width="modalMaxWidth"
        :full-height="context.isMobile" :form-data="occurrenceData" @saved="router.reload()" />
</template>
