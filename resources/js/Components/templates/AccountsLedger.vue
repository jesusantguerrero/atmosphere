<template>
  <div class="border-l border-base-600 px-5 text-base-200">
    <div class="space-y-2">
      <LogerTabButton class="w-full" icon="fa-plus" @click="openAccountModal()">
        Add Account
      </LogerTabButton>
       <Draggable class="dragArea list-group w-full" :list="accounts" handle=".handle"  @end="saveReorder">
        <TransitionGroup>
            <AccountItem
                v-for="account in accounts"
                :key="account.id"
                :account="account"
                :is-selected="isSelectedAccount(account.id)"
                @click="Inertia.visit(`/finance/${account.id}/transactions`)"
            />
        </TransitionGroup>
       </Draggable>
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
import { VueDraggableNext as Draggable } from "vue-draggable-next"

const pageProps = usePage().props;
const isSelectedAccount = (accountId) => {
  return pageProps.value.accountId == accountId;
};

const props = defineProps({
  accounts: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['reordered'])

const isAccountModalOpen = ref(false);
const openAccountModal = () => {
  isAccountModalOpen.value = true;
};

const saveReorder = () => {
    const items = props.accounts.map((item, index) => ({
        ...item,
        index
    }));
    emit("reordered", items)
}
</script>
