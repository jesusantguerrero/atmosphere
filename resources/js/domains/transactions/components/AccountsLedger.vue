
<script setup lang="ts">
import { ref, inject, computed, Ref } from "vue";
import { router } from "@inertiajs/vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next"
// @ts-ignore
import exactMathNode from 'exact-math';

import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import AccountModal from "./AccountModal.vue";
import AccountItem from "./AccountItem.vue";

import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';
import { useAppContextStore } from "@/store";
import { IAccount } from "@/domains/transactions/models/transactions";
import AccountLinkModal from "./AccountLinkModal.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";

const selectedAccountId = inject<Ref<number|null>>('selectedAccountId', ref(null));
const isSelectedAccount = (accountId: number) => {
  return Number(selectedAccountId?.value) === accountId;
};

const props = defineProps<{
    accounts: IAccount[]
}>();

const emit = defineEmits(['reordered'])

const isAccountModalOpen = ref(false);
const accountToEdit = ref({})

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

const budgetAccountsTotal =  computed(() => {
    return props.accounts.reduce((total, account) => {
        return exactMathNode.add(total, account?.balance)
    }, 0)
})
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
       <Draggable class="w-full space-y-1 dragArea list-group" ref="draggableRef" :list="accounts" handle=".handle"  @end="saveReorder" tag="div">
            <AccountItem
                v-for="account in accounts"
                :key="account.id"
                :account="account"
                :is-selected="isSelectedAccount(account.id)"
                @click="onClick(account.id)"
                @edit="openAccountModal(account)"
                @link="openLinkModal(account)"
            />
       </Draggable>
    </div>

    <AccountModal
        v-if="isAccountModalOpen"
        :show="isAccountModalOpen"
        :max-width="modalMaxWidth"
        :form-data="accountToEdit"
        @close="isAccountModalOpen = false"
    />


    <AccountLinkModal
        v-if="accountToEdit"
        :show="isLinkModalOpen"
        :account="accountToEdit"
        :closeable="true"
        title="Link account"
        @save="isLinkModalOpen = false"
        @close="isLinkModalOpen = false"
    />
  </div>
</template>
