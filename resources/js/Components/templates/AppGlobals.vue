<template>
<TransactionModal
    v-model:show="isOpen"
    v-bind="transactionModalState"
/>
<ImportResourceModal
    v-model:show="isImportModalOpen"
/>
</template>

<script setup>
import TransactionModal from '@/Components/TransactionModal.vue';
import { useTransactionModal, transactionModalState } from '@/domains/transactions';
import { useImportModal } from '@/domains/transactions/useImportModal';
import ImportResourceModal from '@/Components/ImportResourceModal.vue';


const {  isOpen } = useTransactionModal()
const { isOpen: isImportModalOpen } = useImportModal()
</script>
