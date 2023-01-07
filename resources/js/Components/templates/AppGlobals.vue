<template>
<TransactionModal
    v-model:show="isOpen"
    v-bind="transactionModalState"
    :max-width="modalMaxWidth"
    :full-height="context.isMobile"
    @saved="onTransactionSaved"
/>

<MoreOptionsModal
    v-model:show="context.isMoreOptionsModalOpen"
    :max-width="modalMaxWidth"
    v-if="context.isMobile"
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
import { useAppContextStore } from '@/store';
import { computed } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import MoreOptionsModal from '../MoreOptionsModal.vue';

const { isOpen, closeTransactionModal } = useTransactionModal()
const onTransactionSaved = () => {
    Inertia.reload()
    closeTransactionModal();
}
const context = useAppContextStore()
const modalMaxWidth = computed(() => {
    return context.isMobile ? 'mobile' : null;
})

const { isOpen: isImportModalOpen } = useImportModal()
</script>
