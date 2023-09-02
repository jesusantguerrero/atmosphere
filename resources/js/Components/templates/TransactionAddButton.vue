<script setup lang="ts">
    // @ts-expect-error: no definitions
    import { AtButton } from 'atmosphere-ui';
    import JetDropdown from '@/Components/atoms/Dropdown.vue'
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';
    import { usePage } from '@inertiajs/vue3';

    import { TRANSACTION_DIRECTIONS,  useTransactionModal } from '@/domains/transactions';
    const  { DEPOSIT, WITHDRAW, TRANSFER } = TRANSACTION_DIRECTIONS;
    const { openTransactionModal } = useTransactionModal();

    const page = usePage().props;
    const open = (mode: string) => {
        const accountId = page.accountId
        openTransactionModal({
            mode: mode,
            transactionData: {
                account_id: accountId ?? "",
            },
        })
    }

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
                <LogerButtonTab class="w-full font-bold" @click="open(DEPOSIT)">
                <IMdiBankTransferIn class="mr-2 text-md" />
                Income
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="open(WITHDRAW)">
                    <IMdiBankTransferOut class="mr-2 text-md" />
                    Expense
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="open('transfer')">
                    <IMdiBankTransfer class="mr-2 text-md" />
                    Transfer
                </LogerButtonTab>
            </div>
        </template>
    </JetDropdown>
</template>

