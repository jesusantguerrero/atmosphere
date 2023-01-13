<template>
    <JetDropdown align="right" width="48">
        <template #trigger>
            <AtButton class="flex items-center shadow-md hover:shadow-md transition hover:shadow-primary hover:ring-4 ring-offset-2 ring-primary/20 space-x-2 text-sm text-white bg-primary" rounded @click="">
                <div class="flex items-center justify-center py-1 rounded-md">
                    <i class="fa fa-plus"></i>
                </div>
                <span>
                    New
                </span>
            </AtButton>
        </template>

        <template #content>
            <div class=" py-1">
                <h4 class="text-body-1/80 px-2"> Transactions: </h4>
                <LogerButtonTab class="w-full font-bold" @click="openTransactionModal({
                    mode: DEPOSIT
                })">
                <IMdiBankTransferIn class="text-md mr-2" />
                Income
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="openTransactionModal({
                    mode: WITHDRAW
                })">
                    <IMdiBankTransferOut class="text-md mr-2" />
                    Expense
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="openTransactionModal({
                    mode: 'transfer'
                })">
                    <IMdiBankTransfer class="text-md mr-2" />
                    Transfer
                </LogerButtonTab>
            </div>
        </template>
    </JetDropdown>
</template>

<script setup>
    import { AtButton } from 'atmosphere-ui';
    import JetDropdown from '@/Components/atoms/Dropdown.vue'
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';

    import { TRANSACTION_DIRECTIONS,  useTransactionModal } from '@/domains/transactions';
    const  { DEPOSIT, WITHDRAW, TRANSFER } = TRANSACTION_DIRECTIONS;
    const { openTransactionModal } = useTransactionModal();
</script>
