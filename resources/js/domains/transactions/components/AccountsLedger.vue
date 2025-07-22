<script setup lang="ts">
import { ref, inject, computed, Ref } from "vue";
import { router } from "@inertiajs/vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next"
// @ts-ignore
import exactMathNode from '@/plugins/exactMath';

import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import AccountModal from "./AccountModal.vue";
import AccountItem from "./AccountItem.vue";

import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';
import { useAppContextStore } from "@/store";
import { IAccount } from "@/domains/transactions/models/transactions";
import AccountLinkModal from "./AccountLinkModal.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";

const selectedAccountId = inject<Ref<number | null>>('selectedAccountId', ref(null));
const isSelectedAccount = (accountId: number) => {
    return Number(selectedAccountId?.value) === accountId;
};

const props = defineProps<{
    accounts: IAccount[]
}>();

const emit = defineEmits(['reordered'])

// Filter state
type FilterType = 'all' | 'reconciled' | 'unreconciled' | 'credit-cards';
const selectedFilter = ref<FilterType>('all');

const isAccountModalOpen = ref(false);
const accountToEdit = ref<Partial<IAccount> | null>()



const openAccountModal = (account = {}) => {
    accountToEdit.value = account;
    isAccountModalOpen.value = true;
};

const saveReorder = () => {
    const items = props.accounts.map((item, index: number) => ({
        ...item,
        index
    }));
    emit("reordered", items)
}

const context = useAppContextStore()
const modalMaxWidth = computed(() => {
    return context.isMobile ? 'mobile' : undefined;
})

const onClick = (accountId: number) => {
    if (isSelectedAccount(accountId)) return
    router.visit(`/finance/accounts/${accountId}`)
}

const isLinkModalOpen = ref(false);
const openLinkModal = (account = {}) => {
    accountToEdit.value = account;
    isLinkModalOpen.value = true;
};

// Filter accounts based on reconciliation status and account type
const filteredAccounts = computed(() => {
    if (selectedFilter.value === 'all') {
        return props.accounts;
    }

    return props.accounts.filter(account => {
        // Credit card filter
        if (selectedFilter.value === 'credit-cards') {
            return account.credit_limit && account.credit_limit > 0;
        }

        // Reconciliation filters
        const isReconciled = account.reconciliation_last &&
            account.reconciliation_last.status === 'completed' &&
            account.reconciliation_last.amount === account.balance;

        return selectedFilter.value === 'reconciled' ? isReconciled : !isReconciled;
    });
});

const budgetAccountsTotal = computed(() => {
    return filteredAccounts.value.reduce((total, account) => {
        return exactMathNode.add(total, account?.balance)
    }, 0)
})

// Filter options
const filterOptions = [
    { key: 'all', label: 'All' },
    { key: 'reconciled', label: 'Reconciled' },
    { key: 'unreconciled', label: 'Unreconciled' },
    { key: 'credit-cards', label: 'Credit Cards' }
] as const;
</script>

<template>
    <div class="px-5 border-l border-base-lvl-1 text-body-1">
        <div class="space-y-2">
            <header class="rounded-t-md flex justify-between items-center">
                <section class="flex items-center space-x-1">
                    <SectionTitle class="min-w-fit">
                        Accounts
                    </SectionTitle>
                    <MoneyPresenter :value="budgetAccountsTotal" class="mr-2 w-full text-primary" />
                </section>
                <section class="flex">
                    <LogerButtonTab class="w-full bg-base-lvl-3" @click="openAccountModal()">
                        <IMdiPlus />
                    </LogerButtonTab>
                </section>
            </header>

            <!-- Filter Toggle -->
            <div class="flex space-x-1 mb-2">
                <button v-for="option in filterOptions" :key="option.key" @click="selectedFilter = option.key" :class="[
                    'px-3 py-1 text-xs rounded-full transition-colors',
                    selectedFilter === option.key
                        ? 'bg-primary text-white'
                        : 'bg-base-lvl-2 text-body-1 hover:bg-base-lvl-3'
                ]">
                    {{ option.label }}
                </button>
            </div>
            <Draggable class="w-full space-y-1 dragArea list-group h-96 overflow-auto ic-scroller" ref="draggableRef"
                :list="filteredAccounts" handle=".handle" @end="saveReorder" tag="div">
                <AccountItem v-for="account in filteredAccounts" :key="account.id" :account="account"
                    :is-selected="isSelectedAccount(account.id)" @click="onClick(account.id)"
                    @edit="openAccountModal(account)" @link="openLinkModal(account)" />
            </Draggable>
        </div>

        <AccountModal v-if="isAccountModalOpen && accountToEdit" :show="isAccountModalOpen" :max-width="modalMaxWidth"
            :form-data="accountToEdit" @close="isAccountModalOpen = false" />

        <AccountLinkModal v-if="accountToEdit" :show="isLinkModalOpen" :account="accountToEdit" :closeable="true"
            title="Link account" @save="isLinkModalOpen = false" @close="isLinkModalOpen = false" />
    </div>
</template>
