<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";

import NumberHider from "@/Components/molecules/NumberHider.vue";
import IconDrag from "@/Components/icons/IconDrag.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import AccountReconciliationAlert from "./AccountReconciliationAlert.vue";
import { computed, toRefs } from "vue";

const props = defineProps({
  account: {
    type: Object,
    required: true,
  },
  isSelected: {
    type: Boolean,
    default: false,
  },
});

const { account  } = toRefs(props)

const isDebt = (amount: number) => {
  return amount < 0;
};

const hasPendingReconciliation = computed(() => {
    return account.value.reconciliation_last?.status == 'pending';
})

const isReconciled = computed(() => {
    return account.value.reconciliation_last?.amount == account.value.balance;
})

const isCreditCard = computed(() => {
    return account.value.credit_limit && account.value.credit_limit > 0;
})

const availableCredit = computed(() => {
    return  parseFloat(account.value.credit_limit) + parseFloat(account.value.balance);
})

const creditLimitDate = computed(() => {
    const formatter = new Intl.PluralRules('en-US', {
        type: 'ordinal'
    })

    const suffixes = new Map([
        ["one", "st"],
        ["two", "nd"],
        ["few", "rd"],
        ["other", "th"],
    ]);
    return account.value.credit_closing_day ? `${account.value.credit_closing_day}${suffixes.get(formatter.select(account.credit_closing_day))}` : '';
})
</script>


<template>
  <div
    class="flex items-center justify-between px-2 py-2 overflow-hidden group rounded-md cursor-pointer transition-colors"
    :class="[
      isSelected
        ? 'bg-primary/10 border-l-2 border-l-primary'
        : 'hover:bg-base-lvl-2 border-l-2 border-l-transparent'
    ]"
    v-bind="$attrs"
    v-on="$attrs"
    :title="account.balance"
  >
    <div class="w-4 hidden h-8 group-hover:inline-block handle cursor-grab shrink-0">
      <IconDrag class="h-8" />
    </div>
    <section class="w-full pl-1 min-w-0">
      <header class="flex items-center gap-1 text-sm truncate">
        <AccountReconciliationAlert v-if="hasPendingReconciliation" class="shrink-0 text-xs" />
        <IMdiLock v-if="isReconciled" class="shrink-0 text-success opacity-70 text-xs" />
        <span class="truncate" :class="isSelected ? 'font-semibold text-body' : 'text-body-1'">
            {{ account.name }}
        </span>
        <span v-if="creditLimitDate" class="text-xs text-body-1/50 shrink-0">{{ creditLimitDate }}</span>
      </header>
      <p class="text-xs mt-0.5 flex items-center gap-1.5" :class="{ 'text-red-400': isDebt(account.balance) }">
        <NumberHider />
        <span :class="isSelected ? 'font-medium' : ''">{{ formatMoney(account.balance, account.currency_code) }}</span>
        <span v-if="isCreditCard" class="text-green-600 text-[10px]">
            {{ formatMoney(availableCredit, account.currency_code) }}
        </span>
      </p>
    </section>
    <div class="hidden group-hover:flex items-center shrink-0">
      <LogerButtonTab
        class="h-7 w-7 flex items-center justify-center rounded hover:text-primary hover:bg-base-lvl-2"
        @click.stop="$emit('edit')"
      >
        <IMdiEdit class="text-xs" />
      </LogerButtonTab>
      <LogerButtonTab
        class="h-7 w-7 flex items-center justify-center rounded hover:text-primary hover:bg-base-lvl-2"
        @click.stop="$emit('link')"
      >
        <IMdiLink class="text-xs" />
      </LogerButtonTab>
    </div>
  </div>
</template>
