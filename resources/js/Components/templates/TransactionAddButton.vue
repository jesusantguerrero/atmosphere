<script setup lang="ts">
    import { AtButton } from 'atmosphere-ui';
    import JetDropdown from '@/Components/atoms/Dropdown.vue'
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';
    import { router } from '@inertiajs/vue3';
    import { onMounted } from "vue";

    import { TRANSACTION_DIRECTIONS,  useTransactionModal } from '@/domains/transactions';
    const  { DEPOSIT, WITHDRAW, TRANSFER } = TRANSACTION_DIRECTIONS;
    const { openTransactionModal } = useTransactionModal();

    onMounted(() => {
        router.on('start', (evt) => {
            console.log(`The route changed to ${evt.detail.visit.url}`, evt.detail)
        })
    })

</script>

<template>
    <JetDropdown align="right" width="48">
        <template #trigger>
            <AtButton class="flex items-center space-x-2 text-sm text-white transition shadow-md hover:shadow-md hover:shadow-primary hover:ring-4 ring-offset-2 ring-primary/20 bg-primary" rounded @click="">
                <div class="flex items-center justify-center py-1 rounded-md">
                    <i class="fa fa-plus"></i>
                </div>
                <span>
                    New
                </span>
            </AtButton>
        </template>

        <template #content>
            <div class="py-1 ">
                <h4 class="px-2 text-body-1/80"> Transactions: </h4>
                <LogerButtonTab class="w-full font-bold" @click="openTransactionModal({
                    mode: DEPOSIT,
                    transactionData: {
                        account_id: '',
                    },
                })">
                <IMdiBankTransferIn class="mr-2 text-md" />
                Income
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="openTransactionModal({
                    mode: WITHDRAW
                })">
                    <IMdiBankTransferOut class="mr-2 text-md" />
                    Expense
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="openTransactionModal({
                    mode: 'transfer'
                })">
                    <IMdiBankTransfer class="mr-2 text-md" />
                    Transfer
                </LogerButtonTab>
            </div>
        </template>
    </JetDropdown>
</template>

