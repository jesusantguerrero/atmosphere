<template>
  <TransactionModal
    v-model:show="isOpen"
    v-bind="transactionModalState"
    :max-width="modalMaxWidth"
    :full-height="context.isMobile"
    @saved="onTransactionSaved"
    @close="onTransactionSaved"
  />

  <MoreOptionsModal
    v-model:show="context.isMoreOptionsModalOpen"
    :max-width="modalMaxWidth"
    v-if="context.isMobile"
  />

  <ImportResourceModal v-model:show="isImportModalOpen" />
</template>

<script setup>
import TransactionModal from "@/domains/transactions/components/TransactionModal.vue";
import { useTransactionModal, transactionModalState } from "@/domains/transactions";
import { useImportModal } from "@/domains/transactions/useImportModal";
import ImportResourceModal from "@/Components/ImportResourceModal.vue";
import { useAppContextStore } from "@/store";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import MoreOptionsModal from "../MoreOptionsModal.vue";

const { isOpen, closeTransactionModal } = useTransactionModal();
const onTransactionSaved = () => {
    debugger
    router.reload();
    closeTransactionModal();
};
const context = useAppContextStore();
const modalMaxWidth = computed(() => {
  return context.isMobile ? "mobile" : null;
});

const { isOpen: isImportModalOpen } = useImportModal();

var es = new EventSource('http://localhost:3000/.well-known/mercure?topic=' + encodeURIComponent('http://example/budget-calculated'));
es.addEventListener('message', (messageEvent) => {
    var eventData = JSON.parse(messageEvent.data);
    console.log(eventData);
});
</script>
