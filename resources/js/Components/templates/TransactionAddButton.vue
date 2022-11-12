<template>
    <JetDropdown align="right" width="48">
        <template #trigger>
            <AtButton class="flex items-center px-2 space-x-2 text-sm text-white bg-primary" rounded @click="" v-if="!isOnboarding">
                <div class="flex items-center justify-center px-1 py-1 rounded-md">
                    <i class="fa fa-plus"></i>
                </div>
                <span>
                    Add new
                </span>
                <div class="flex items-center justify-center py-1 rounded-md">
                    <i class="fa fa-chevron-down"></i>
                </div>
            </AtButton>
        </template>

        <template #content>
            <LogerTabButton class="w-full" @click="openTransactionModal({
                mode: DEPOSIT
            })">Income</LogerTabButton>
            <LogerTabButton class="w-full" @click="openTransactionModal({
                mode: WITHDRAW
            })">Expense</LogerTabButton>
            <LogerTabButton class="w-full" @click="openTransactionModal({
                mode: 'transfer'
            })">Transfer</LogerTabButton>
        </template>
    </JetDropdown>
</template>

<script setup>
    import { AtButton } from 'atmosphere-ui';
    import JetDropdown from '@/Components/atoms/Dropdown.vue'
    import LogerTabButton from '@/Components/atoms/LogerTabButton.vue';
    
    import { TRANSACTION_DIRECTIONS,  useTransactionModal } from '@/domains/transactions';
    const  { DEPOSIT, WITHDRAW, TRANSFER } = TRANSACTION_DIRECTIONS;
    const { openTransactionModal } = useTransactionModal();
</script>