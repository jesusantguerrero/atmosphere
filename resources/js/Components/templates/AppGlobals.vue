<script setup lang="ts">
import TransactionModal from "@/domains/transactions/components/TransactionModal.vue";
import { useTransactionModal, transactionModalState } from "@/domains/transactions";
import { useImportModal } from "@/domains/transactions/useImportModal";
import ImportResourceModal from "@/Components/ImportResourceModal.vue";
import { useAppContextStore } from "@/store";
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import MoreOptionsModal from "../MoreOptionsModal.vue";
import { config } from "@/config/index";

const { isOpen, closeTransactionModal } = useTransactionModal();
const onTransactionSaved = () => {
  debugger;
  router.reload();
  closeTransactionModal();
};
const context = useAppContextStore();
const modalMaxWidth = computed(() => {
  return context.isMobile ? "mobile" : null;
});

const { isOpen: isImportModalOpen } = useImportModal();

const url = new URL(config.MERCURE_URL);
url.searchParams.append("topic", "https://example.com/main");
url.searchParams.append("topic", "https://example.com/users/jesus");
var es = new EventSource(url);
es.onmessage = (messageEvent) => {
  var eventData = JSON.parse(messageEvent.data);
  console.log(eventData);
};
</script>

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
