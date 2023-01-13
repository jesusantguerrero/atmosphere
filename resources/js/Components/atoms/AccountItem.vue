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
    <div class="w-full pl-2">
      <strong class="flex flex-wrap overflow-hidden overflow-ellipsis">
        {{ account.name }}
      </strong>
      <p class="relative text-sm" :class="{ 'text-red-400': isDebt(account.balance) }">
        <NumberHider />
        {{ formatMoney(account.balance, account.currency_code) }}
      </p>
    </div>
    <div>
      <LogerButtonTab
        class="h-full flex items-center justify-center px-0.5 rounded-md hover:text-primary hover:bg-base-lvl-2"
        @click.stop="$emit('edit')"
      >
        <i class="fa fa-edit"></i>
      </LogerButtonTab>
    </div>
  </div>
</template>

<script setup>
import formatMoney from "@/utils/formatMoney";

import NumberHider from "@/Components/molecules/NumberHider.vue";
import IconDrag from "../icons/IconDrag.vue";
import LogerButtonTab from "./LogerButtonTab.vue";

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

const isDebt = (amount) => {
  return amount < 0;
};
</script>
