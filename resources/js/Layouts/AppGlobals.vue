<template>
<TransactionModal
    v-model:show="isOpen"
    v-bind="transferModalState"
/>
</template>

<script setup>
import TransactionModal from '@/Components/TransactionModal.vue';
import { useTransactionModal, transferModalState } from '@/domains/transactions';


const {  isOpen } = useTransactionModal()
</script>
