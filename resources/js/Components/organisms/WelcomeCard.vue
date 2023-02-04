<template>
  <div
    class="px-5 py-3 transition border divide-y rounded-lg divide-base border-base bg-base-lvl-3"
    :class="[cardShadow]"
  >
    <div class="items-center pb-2 md:justify-between md:flex">
      <h1 class="font-bold text-body">
        {{ message }} <span class="text-primary">{{ username }}</span>
      </h1>
      <div class="space-x-2">
        <AtButton
          class="text-sm text-primary"
          rounded
          @click="router.visit(route('budgets.index'))"
        >
          <i class="fa fa-wallet"></i>
          Edit budget
        </AtButton>
      </div>
    </div>
    <slot></slot>
  </div>
</template>

<script setup>
import { AtButton } from "atmosphere-ui";
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";

import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import NumberHider from "@/Components/molecules/NumberHider.vue";

import formatMoney from "@/utils/formatMoney";
import { useTransactionModal } from "@/domains/transactions";

const props = defineProps({
  username: {
    type: String,
    required: true,
  },
  budget: {
    type: Number,
  },
  expenses: {
    type: [Number, String],
  },
  message: {
    default: "Welcome to Loger",
  },
});

const { t } = useI18n();
const { isOpen: isTransferModalOpen } = useTransactionModal();

const openedTransaction = ref(null);

const sections = computed(() => ({
  expenses: {
    label: t("Current Expenses"),
    value: props.expenses,
  },
  budget: {
    label: t("Monthly Budget"),
    value: props.budget,
  },
}));

const isMultiple = (value) => {
  return Array.isArray(value);
};
</script>
