<template>
<TransactionModal
    v-model:show="isOpen"
    v-bind="transferModalState"
/>
</template>

<script setup>
import TransactionModal from '@/Components/TransactionModal.vue';
import { useTransferModal, transferModalState } from '@/utils/useTransferModal';


const {  isOpen } = useTransferModal()
</script>
