<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";

import NumberHider from "@/Components/molecules/NumberHider.vue";
import IconDrag from "@/Components/icons/IconDrag.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import AccountReconciliationAlert from "./AccountReconciliationAlert.vue";
import { computed, onMounted, ref, toRefs } from "vue";
import IMdiCheckCircle from "~icons/mdi/check-circle";
import IMdiEdit from "~icons/mdi/pencil";
import IMdiLink from "~icons/mdi/link";

const props = defineProps<{
  account: Record<string, any>,
  isSelected: boolean,
  index: number
}>();

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
    return account.value.credit_closing_day ? ` - ${account.value.credit_closing_day}${suffixes.get(formatter.select(account.credit_closing_day))}` : '';
})


const CreditCardView = ref<HTMLElement>();

onMounted(() => {
    if (!CreditCardView.value) return
    CreditCardView.value.style.transform = `translate(${props.index * 8}px, ${0}px) scale(${1 - props.index * 0.025})`
    CreditCardView.value.style.zIndex = `${6 - props.index}`;
    CreditCardView.value.style.opacity =  `${1 - props.index * 0.1}`
})
</script>

<template>
  <div
    class="card w-full rounded-lg border border-gray-200 bg-white p-4 cursor-pointer hover:border-gray-300 transition-all relative overflow-hidden"
    :class="{ 'border-primary border-2 ring-1 ring-primary': isSelected }"
    ref="CreditCardView"
    v-bind="$attrs"
    v-on="$attrs"
    :title="account.balance"
    :data-slide="index"
  >
    <!-- Header -->
    <div class="flex justify-between items-start gap-2 mb-3 pb-3 border-b border-gray-100">
      <div class="flex-1 min-w-0">
        <p class="text-xs text-gray-500 font-medium truncate">{{ account.name }}</p>
      </div>

      <!-- Status Indicator -->
      <div class="flex-shrink-0">
        <AccountReconciliationAlert v-if="hasPendingReconciliation" />
        <div v-else-if="isReconciled" class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
          <IMdiCheckCircle class="text-green-600 text-xs" />
        </div>
      </div>
    </div>

    <!-- Balance Section -->
    <div class="mb-3">
      <p class="text-xs text-gray-500 mb-0.5">Current Balance</p>
      <p class="text-lg font-bold text-gray-900">
        <NumberHider />
        {{ formatMoney(account.balance, account.currency_code) }}
      </p>
    </div>

    <!-- Available Credit -->
    <div class="flex justify-between items-end">
      <div class="flex-1">
        <p class="text-xs text-gray-500">Available</p>
        <p class="text-sm font-semibold text-gray-700">{{ formatMoney(availableCredit, account.currency_code) }}</p>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-1">
        <button
          class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/5 rounded transition-colors flex-shrink-0"
          @click.stop="$emit('edit')"
          title="Edit account"
        >
          <IMdiEdit class="w-4 h-4" />
        </button>
        <button
          class="p-1.5 text-gray-400 hover:text-primary hover:bg-primary/5 rounded transition-colors flex-shrink-0"
          @click.stop="$emit('link')"
          title="Link account"
        >
          <IMdiLink class="w-4 h-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">


</style>

