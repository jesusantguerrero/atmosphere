
<script setup lang="ts">
import { ref, computed } from "vue";

import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import AccountModal from "./AccountModal.vue";

import { useAppContextStore } from "@/store";
import { IAccount } from "@/domains/transactions/models/transactions";
import AccountLinkModal from "./AccountLinkModal.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import CreditCardItem from "./CreditCardItem.vue";


const props = defineProps<{
    accounts: IAccount[]
}>();

const emit = defineEmits(['update:model-value']);

const selectedAccountId = ref(props.accounts?.at?.(0)?.id);
const isSelectedAccount = (accountId: number) => {
  return Number(selectedAccountId?.value) === accountId;
};


const isAccountModalOpen = ref(false);
const accountToEdit = ref({})

const openAccountModal = (account = {}) => {
    accountToEdit.value = account;
    isAccountModalOpen.value = true;
};

const context = useAppContextStore()
const modalMaxWidth = computed(() => {
    return context.isMobile ? 'mobile' : undefined;
})


const isLinkModalOpen = ref(false);
const openLinkModal = (account = {}) => {
    accountToEdit.value = account;
    isLinkModalOpen.value = true;
};

const slideIndex = ref(0);

const clonedCards = ref(props.accounts?.map?.(item => item) ?? []);

let setIndex = () => {
    clonedCards.value.unshift(clonedCards.value.pop())
    console.log('shift', clonedCards.value.map(item => item.name));
    emit('update:model-value', clonedCards.value[0])
}

setIndex()

</script>

<template>
  <div class="border-l border-base-lvl-1 text-body-1">
    <div class="space-y-2 px-5">
       <section
            class="w-full cards-box relative px-5"
            :data-slide="slideIndex"
        >
            <CreditCardItem
                v-for="(account, index) in clonedCards"
                :key="`${account.id}-${index}`"
                :account="account"
                :index="index"
                :is-selected="isSelectedAccount(account.id)"
                @click="setIndex(index)"
                @edit="openAccountModal(account)"
                @link="openLinkModal(account)"
                @set-slide="slideIndex = $event"
            />
       </section>
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


<style>
.cards-box {
	position: relative;
	transform: translateX(-15px);
    height: 160px;
}

.cards-box .card {
	position: absolute;
	top: 0;
	left: 0;
	transition: all 0.8s cubic-bezier(0.18, 0.98, 0.45, 1);
	box-shadow: 0 2px 10px 0 rgba(0, 0, 0, 0.07);
}

.cards-box .card[data-slide='0'] {
	transition: all 0.32s cubic-bezier(0.18, 0.98, 0.45, 1);
}
</style>
