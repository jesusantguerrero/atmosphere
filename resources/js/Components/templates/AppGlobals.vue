<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

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

const { isOpen: isImportModalOpen } = useImportModal();

if (config.MERCURE_URL) {
    const url = new URL(config.MERCURE_URL);
    url.searchParams.append("topic", "https://example.com/main");
    url.searchParams.append("topic", "https://example.com/users/jesus");
    var es = new EventSource(url);
    es.onmessage = (messageEvent) => {
        var eventData = JSON.parse(messageEvent.data);
        console.log(eventData);
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
    <TransactionModal v-model:show="isOpen" v-bind="transactionModalState" :max-width="modalMaxWidth"
        :full-height="context.isMobile" @saved="onTransactionSaved" @close="onTransactionSaved" />

    <MoreOptionsModal v-model:show="context.isMoreOptionsModalOpen" :max-width="modalMaxWidth"
        v-if="context.isMobile" />

    <PaymentFormModal v-if="paymentModalState" v-bind="paymentModalState?.data" v-model="isPaymentModalOpen"
        @saved="onTransactionSaved" />

    <ImportResourceModal v-model:show="isImportModalOpen" />


    <OccurrenceCheckModal v-model:show="isOccurrenceModalOpen" :max-width="modalMaxWidth"
        :full-height="context.isMobile" :form-data="occurrenceData" @saved="router.reload()" />
</template>
