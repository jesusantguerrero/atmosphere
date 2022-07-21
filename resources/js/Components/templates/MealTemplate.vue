<template>
  <section class="pl-6 pb-20 max-w-screen-2xl">
    <header class="" v-if="showMealTypes">
      <article class="flex w-full justify-between mb-2">
        <SectionTitle> Meals </SectionTitle>
        <LogerTabButton class="font-bold">Add Meal</LogerTabButton>
      </article>
      <article class="flex space-x-4">
        <div
          v-for="mealType in pageProps.mealTypes"
          class="cursor-pointer font-bold text-white border-primary-400 transition rounded-md bg-gradient-to-br from-purple-400 to-primary-500 hover:bg-primary-500 h-20 w-full flex flex-col items-center justify-center"
        >
          <h4 class="capitalize">
            {{ mealType.name }}
          </h4>
          <p>{{ mealType.description }}</p>
        </div>
      </article>
    </header>

    <div class="w-full mt-4">
      <slot />
    </div>

    <TransactionModal v-bind="transferConfig" v-model:show="isTransferModalOpen" />
    <ImportResourceModal v-model:show="isImportModalOpen" />
  </section>
</template>

<script setup lang="ts">
import { provide, reactive, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import TransactionModal from "@/Components/TransactionModal.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import ImportResourceModal from "@/Components/ImportResourceModal.vue";

const pageProps = usePage().props;

defineProps({
  title: {
    type: String,
  },
  showMealTypes: {
    type: Boolean,
    default: true
  }
});

// Transaction modal things
const isTransferModalOpen = ref(false);
const transferConfig = reactive({
  recurrence: false,
  automatic: false,
  transactionData: null,
});

const openTransactionModal = (isRecurrent, automatic) => {
  isTransferModalOpen.value = true;
  transferConfig.recurrence = isRecurrent;
  transferConfig.automatic = automatic;
};

const openTransactionModalForEdit = (transaction) => {
  transferConfig.transactionData = transaction;
  isTransferModalOpen.value = true;
};

provide("openTransactionModal", openTransactionModal);
provide("openTransactionModalForEdit", openTransactionModalForEdit);

defineExpose({
  openTransactionModal,
  openTransactionModalForEdit,
});
</script>
