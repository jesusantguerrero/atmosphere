<script setup lang="ts">
import { ref, inject, computed, Ref, reactive, watch } from "vue";
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

const selectedAccountId = inject<Ref<number | null>>('selectedAccountId', ref(null));
const isSelectedAccount = (accountId: number) => {
    return Number(selectedAccountId?.value) === accountId;
};

const props = defineProps<{
    accounts: IAccount[]
}>();

const emit = defineEmits(['reordered'])

// Filter state — persist via localStorage
type FilterType = 'all' | 'reconciled' | 'unreconciled' | 'credit-cards';
const FILTER_KEY = 'loger:accounts-filter';
const validFilters: FilterType[] = ['all', 'reconciled', 'unreconciled', 'credit-cards'];
const stored = localStorage.getItem(FILTER_KEY) as FilterType | null;
const selectedFilter = ref<FilterType>(stored && validFilters.includes(stored) ? stored : 'all');

watch(selectedFilter, (val) => {
    localStorage.setItem(FILTER_KEY, val);
});

const isAccountModalOpen = ref(false);
const accountToEdit = ref<Partial<IAccount> | null>()

const openAccountModal = (account = {}) => {
    accountToEdit.value = account;
    isAccountModalOpen.value = true;
};

const saveReorder = () => {
    // Combine both groups in visual order: credit cards first, then bank accounts
    const ordered = [...creditCards.value, ...bankAccounts.value];
    const items = ordered.map((item, index: number) => ({
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

// Separate credit cards from regular accounts
const isCreditCard = (account: IAccount) => {
    return account.credit_limit && account.credit_limit > 0;
};

// Filter accounts based on reconciliation status and account type
const filteredAccounts = computed(() => {
    let accounts = props.accounts;

    if (selectedFilter.value === 'credit-cards') {
        return accounts.filter(isCreditCard);
    }
    if (selectedFilter.value === 'reconciled') {
        return accounts.filter(a => a.reconciliation_last?.status === 'completed' && a.reconciliation_last?.amount === a.balance);
    }
    if (selectedFilter.value === 'unreconciled') {
        return accounts.filter(a => !a.reconciliation_last || a.reconciliation_last?.status !== 'completed' || a.reconciliation_last?.amount !== a.balance);
    }
    return accounts;
});

const bankAccounts = computed(() => filteredAccounts.value.filter(a => !isCreditCard(a)));
const creditCards = computed(() => filteredAccounts.value.filter(isCreditCard));

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
    { key: 'credit-cards', label: 'Cards' }
] as const;

const collapsed = reactive<Record<string, boolean>>({});
const toggleGroup = (group: string) => {
    collapsed[group] = !collapsed[group];
};
</script>

<template>
    <div class="text-body-1">
        <!-- Header -->
        <header class="flex items-center justify-between pb-2">
            <div class="flex items-center gap-1">
                <h3 class="text-sm font-bold text-body">Accounts</h3>
                <MoneyPresenter :value="budgetAccountsTotal" class="text-xs text-primary" />
            </div>
            <LogerButtonTab class="w-7 h-7 flex items-center justify-center rounded hover:bg-base-lvl-2" @click="openAccountModal()">
                <IMdiPlus class="text-sm" />
            </LogerButtonTab>
        </header>

        <!-- Compact filter -->
        <div class="flex gap-1 pb-2 overflow-x-auto">
            <button v-for="option in filterOptions" :key="option.key" @click="selectedFilter = option.key" :class="[
                'px-2 py-0.5 text-[11px] rounded-full transition-colors whitespace-nowrap',
                selectedFilter === option.key
                    ? 'bg-primary/15 text-primary font-medium'
                    : 'text-body-1/60 hover:bg-base-lvl-2'
            ]">
                {{ option.label }}
            </button>
        </div>

        <!-- Account list -->
        <div class="h-[calc(100vh-280px)] overflow-auto ic-scroller space-y-3">
            <!-- Credit cards group (first — late fees) -->
            <section v-if="creditCards.length">
                <button v-if="bankAccounts.length" class="w-full flex items-center justify-between px-2 pb-1 cursor-pointer" @click="toggleGroup('credit-cards')">
                    <h4 class="text-[10px] uppercase tracking-wider text-body-1/50 font-semibold">
                        Credit Cards
                    </h4>
                    <IMdiChevronDown class="text-body-1/40 text-xs transition-transform" :class="{ '-rotate-90': collapsed['credit-cards'] }" />
                </button>
                <Draggable v-show="!collapsed['credit-cards']" class="space-y-0.5" :list="creditCards" handle=".handle" @end="saveReorder" tag="div">
                    <AccountItem v-for="account in creditCards" :key="account.id" :account="account"
                        :is-selected="isSelectedAccount(account.id)" @click="onClick(account.id)"
                        @edit="openAccountModal(account)" @link="openLinkModal(account)" />
                </Draggable>
            </section>

            <!-- Bank accounts group -->
            <section v-if="bankAccounts.length">
                <button v-if="creditCards.length" class="w-full flex items-center justify-between px-2 pb-1 cursor-pointer" @click="toggleGroup('bank')">
                    <h4 class="text-[10px] uppercase tracking-wider text-body-1/50 font-semibold">
                        Bank Accounts
                    </h4>
                    <IMdiChevronDown class="text-body-1/40 text-xs transition-transform" :class="{ '-rotate-90': collapsed['bank'] }" />
                </button>
                <Draggable v-show="!collapsed['bank']" class="space-y-0.5" :list="bankAccounts" handle=".handle" @end="saveReorder" tag="div">
                    <AccountItem v-for="account in bankAccounts" :key="account.id" :account="account"
                        :is-selected="isSelectedAccount(account.id)" @click="onClick(account.id)"
                        @edit="openAccountModal(account)" @link="openLinkModal(account)" />
                </Draggable>
            </section>

            <!-- Empty state -->
            <p v-if="!filteredAccounts.length" class="text-xs text-center text-body-1/40 py-4">
                No accounts match this filter
            </p>
        </div>

        <AccountModal v-if="isAccountModalOpen && accountToEdit" :show="isAccountModalOpen" :max-width="modalMaxWidth"
            :form-data="accountToEdit" @close="isAccountModalOpen = false" />

        <AccountLinkModal v-if="accountToEdit" :show="isLinkModalOpen" :account="accountToEdit" :closeable="true"
            title="Link account" @save="isLinkModalOpen = false" @close="isLinkModalOpen = false" />
    </div>
</template>
