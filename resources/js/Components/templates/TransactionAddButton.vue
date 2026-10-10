<script setup lang="ts">
    // @ts-expect-error: no definitions
    import { AtButton } from 'atmosphere-ui';
    import JetDropdown from '@/Components/atoms/Dropdown.vue'
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';
    import { usePage, router } from '@inertiajs/vue3';

    import { TRANSACTION_DIRECTIONS,  useTransactionModal } from '@/domains/transactions';
    import { useImportModal } from '@/domains/transactions/useImportModal';
    import { useToggleModal } from '@/domains/app/useToggleModal';
    const  { DEPOSIT, WITHDRAW, TRANSFER } = TRANSACTION_DIRECTIONS;
    const { openTransactionModal } = useTransactionModal();
    const { openModal: openBulkPlanner } = useToggleModal('bulkPlanner');
    const { openModal: openImport } = useImportModal();
    const { openModal: openOccurrence } = useToggleModal('occurrence');

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
                    {{ $t('New') }}
                </span>
            </AtButton>
        </template>

        <template #content>
            <div class="py-1 ">
                <h4 class="px-2 text-body-1/80"> {{ $t('Transactions') }}: </h4>
                <LogerButtonTab class="w-full font-bold" @click="open(DEPOSIT)">
                <IMdiBankTransferIn class="mr-2 text-md" />
                {{ $t('Income') }}
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="open(WITHDRAW)">
                    <IMdiBankTransferOut class="mr-2 text-md" />
                    {{ $t('Expense') }}
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="open('transfer')">
                    <IMdiBankTransfer class="mr-2 text-md" />
                    {{ $t('Transfer') }}
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="openImport()">
                    <IMdiFileImport class="mr-2 text-md" />
                    {{ $t('Import') }}
                </LogerButtonTab>

                <h4 class="px-2 mt-2 border-t border-base pt-2 text-body-1/80"> {{ $t('Accounts') }}: </h4>
                <LogerButtonTab class="w-full font-bold" @click="router.visit('/finance/accounts?newAccount=1')">
                    <IMdiBankPlus class="mr-2 text-md" />
                    {{ $t('New account') }}
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="router.visit('/finance/reconciliation')">
                    <IMdiScaleBalance class="mr-2 text-md" />
                    {{ $t('New reconciliation') }}
                </LogerButtonTab>

                <h4 class="px-2 mt-2 border-t border-base pt-2 text-body-1/80"> {{ $t('Budget') }}: </h4>
                <LogerButtonTab class="w-full font-bold" @click="router.visit('/budgets')">
                    <IMdiTagPlus class="mr-2 text-md" />
                    {{ $t('New category') }}
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="router.visit('/finance/goals')">
                    <IMdiBullseyeArrow class="mr-2 text-md" />
                    {{ $t('New goal') }}
                </LogerButtonTab>

                <h4 class="px-2 mt-2 border-t border-base pt-2 text-body-1/80"> {{ $t('Planner') }}: </h4>
                <LogerButtonTab class="w-full font-bold" @click="openBulkPlanner()">
                    <IMdiCalendarMultipleCheck class="mr-2 text-md" />
                    {{ $t('Plan a batch') }}
                </LogerButtonTab>
                <LogerButtonTab class="w-full font-bold" @click="openOccurrence()">
                    <IMdiBellPlus class="mr-2 text-md" />
                    {{ $t('New reminder') }}
                </LogerButtonTab>
            </div>
        </template>
    </JetDropdown>
</template>
