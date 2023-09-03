<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";

import NumberHider from "@/Components/molecules/NumberHider.vue";
import IconDrag from "@/Components/icons/IconDrag.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import AccountReconciliationAlert from "./AccountReconciliationAlert.vue";

defineProps({
  account: {
    type: Object,
    required: true,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
});

const isDebt = (amount: number) => {
  return amount < 0;
};
</script>


<template>
  <div
    class="flex justify-between px-2 py-2 overflow-hidden rounded-md cursor-pointer hover:bg-base group"
    :class="{ 'bg-base': isSelected }"
    v-bind="$attrs"
    v-on="$attrs"
    :title="account.balance"
  >
    <div class="w-4">
      <IconDrag class="hidden h-10 group-hover:inline-block handle" />
    </div>
    <section class="w-full pl-2">
      <header class="flex flex-wrap items-center overflow-hidden overflow-ellipsis">
        <AccountReconciliationAlert v-if="account.reconciliations_pending" class="mr-1" />
        <span>
            {{ account.name }}
        </span>
      </header>
      <p class="relative text-sm" :class="{ 'text-red-400': isDebt(account.balance) }">
        <NumberHider />
        {{ formatMoney(account.balance, account.currency_code) }}
      </p>
    </section>
    <div class="flex">
      <LogerButtonTab
        class="h-full flex items-center justify-center px-0.5 rounded-md hover:text-primary hover:bg-base-lvl-2"
        @click.stop="$emit('edit')"
      >
        <IMdiEdit />
      </LogerButtonTab>
      <LogerButtonTab
        class="h-full flex items-center justify-center px-0.5 rounded-md hover:text-primary hover:bg-base-lvl-2"
        @click.stop="$emit('link')"
      >
        <IMdiLink />
      </LogerButtonTab>
    </div>
  </div>
</template>

