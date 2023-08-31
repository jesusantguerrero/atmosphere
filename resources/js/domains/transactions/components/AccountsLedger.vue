
<script setup lang="ts">
import { ref, inject, computed, Ref } from "vue";
import { router } from "@inertiajs/vue3";
import { VueDraggableNext as Draggable } from "vue-draggable-next"

import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import AccountModal from "./AccountModal.vue";
import AccountItem from "./AccountItem.vue";

import { useAppContextStore } from "@/store";
import { IAccount } from "@/domains/transactions/models/transactions";

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
</script>

<template>
  <div class="px-5 border-l border-base-lvl-1 text-body-1">
    <div class="space-y-2">
      <LogerButtonTab class="w-full bg-base-lvl-3" icon="fa-plus" @click="openAccountModal()">
        Add Account
      </LogerButtonTab>
       <Draggable class="w-full space-y-1 dragArea list-group" ref="draggableRef" :list="accounts" handle=".handle"  @end="saveReorder" tag="div">
            <AccountItem
                v-for="account in accounts"
                :key="account.id"
                :account="account"
                :is-selected="isSelectedAccount(account.id)"
                @click="onClick(account.id)"
                @edit="openAccountModal(account)"
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
  </div>
</template>
