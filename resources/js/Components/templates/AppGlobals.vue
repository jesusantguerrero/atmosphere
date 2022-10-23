<template>
<TransactionModal
    v-model:show="isOpen"
    v-bind="transferModalState"
/>
<ImportResourceModal
    v-model:show="isImportModalOpen"
/>
</template>

<script setup>
import TransactionModal from '@/Components/TransactionModal.vue';
import { useTransactionModal, transferModalState } from '@/domains/transactions';
import { useImportModal } from '@/domains/transactions/useImportModal';
import ImportResourceModal from '@/Components/ImportResourceModal.vue';


const {  isOpen } = useTransactionModal()
const { isOpen: isImportModalOpen } = useImportModal()
</script>
