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
                @click="Inertia.visit(`/finance/accounts/${account.id}`)"
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

<script setup>
import { ref, inject, computed } from "vue";
import { Inertia } from "@inertiajs/inertia";
import AccountItem from "@/Components/atoms/AccountItem.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import AccountModal from "@/Components/organisms/AccountModal.vue";
import { VueDraggableNext as Draggable } from "vue-draggable-next"
import { useAppContextStore } from "@/store";

const selectedAccountId = inject('selectedAccountId', null);
const isSelectedAccount = (accountId) => {
  return Number(selectedAccountId?.value) === accountId;
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

const openAccountModal = (account = {}) => {
    accountToEdit.value = account;
    isAccountModalOpen.value = true;
};

const saveReorder = () => {
    const items = props.accounts.map((item, index) => ({
        ...item,
        index
    }));
    emit("reordered", items)
}

const context = useAppContextStore()
const modalMaxWidth = computed(() => {
    return context.isMobile ? 'mobile' : null;
})
</script>
