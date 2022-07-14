<template>
  <div class="rounded-md border-l border-slate-600 px-5 text-slate-200">
    <div class="space-y-2">
      <LogerTabButton class="w-full" icon="fa-plus" @click="openAccountModal()">
        Add Account
      </LogerTabButton>
      <AccountItem
        v-for="account in accounts"
        :key="account.id"
        :account="account"
        :is-selected="isSelectedAccount(account.id)"
        @click="Inertia.visit(`/finance/${account.id}/transactions`)"
      />
    </div>

    <AccountModal v-if="isAccountModalOpen" :show="isAccountModalOpen" @close="isAccountModalOpen = false"/>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/inertia-vue3";
import AccountItem from "@/Components/atoms/AccountItem.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import AccountModal from "@/Components/organisms/AccountModal.vue";

const pageProps = usePage().props;
const isSelectedAccount = (accountId) => {
  return pageProps.value.accountId == accountId;
};

defineProps({
  accounts: {
    type: Array,
    default: () => [],
  },
});

const isAccountModalOpen = ref(false);
const openAccountModal = () => {
  isAccountModalOpen.value = true;
};
</script>
