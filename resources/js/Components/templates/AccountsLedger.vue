<template>
  <div class="px-5 border-l border-base-lvl-1 text-body-1">
    <div class="space-y-2">
      <LogerTabButton class="w-full bg-base-lvl-3" icon="fa-plus" @click="openAccountModal()">
        Add Account
      </LogerTabButton>
       <Draggable class="w-full space-y-1 dragArea list-group" ref="draggableRef" :list="accounts" handle=".handle"  @end="saveReorder" tag="div">
            <AccountItem
                v-for="account in accounts"
                :key="account.id"
                :account="account"
                :is-selected="isSelectedAccount(account.id)"
                @click="Inertia.visit(`/finance/transactions?filter[account_id]=${account.id}`)"
                @edit="onEdit(account)"
            />
       </Draggable>
    </div>

    <AccountModal
        v-if="isAccountModalOpen"
        :show="isAccountModalOpen"
        :form-data="accountToEdit"
        @close="isAccountModalOpen = false"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, inject } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";
import AccountItem from "@/Components/atoms/AccountItem.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import AccountModal from "@/Components/organisms/AccountModal.vue";
import { VueDraggableNext as Draggable } from "vue-draggable-next"
import autoAnimate from "@formkit/auto-animate"

const selectedAccountId = inject('selectedAccountId', null);
const isSelectedAccount = (accountId) => {
  return Number(selectedAccountId) === accountId;
};

const props = defineProps({
  accounts: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['reordered'])

const isAccountModalOpen = ref(false);
const accountToEdit = ref({})

const openAccountModal = () => {
  isAccountModalOpen.value = true;
};

const onEdit = (account) =>  {
    accountToEdit.value = account;
    openAccountModal();
}

const saveReorder = () => {
    const items = props.accounts.map((item, index) => ({
        ...item,
        index
    }));
    emit("reordered", items)
}

</script>
