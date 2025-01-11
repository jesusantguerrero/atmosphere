<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";

import NumberHider from "@/Components/molecules/NumberHider.vue";
import IconDrag from "@/Components/icons/IconDrag.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import AccountReconciliationAlert from "./AccountReconciliationAlert.vue";
import { computed, onMounted, ref, toRefs } from "vue";

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
    class="card w-full bg-base border-2 border-[dodgerblue] cursor-pointer rounded-md h-[160px] px-6 py-3"
    :class="{ 'bg-base': isSelected }"
    ref="CreditCardView"
    v-bind="$attrs"
    v-on="$attrs"
    :title="account.balance"
    :data-slide="index"
  >
    <div class="w-4 hidden h-10 group-hover:inline-block handle cursor-grab">
      <IconDrag class="h-10" />
    </div>
    <section class="w-full pl-2">
      <header class="flex flex-wrap items-center overflow-hidden overflow-ellipsis">
        <AccountReconciliationAlert v-if="hasPendingReconciliation" class="mr-1" />
        <IMdiLock v-if="isReconciled" class="mr-1 text-success opacity-70 text-xs" />
        <span>
            {{ account.name }} {{ creditLimitDate }}
        </span>
      </header>
      <p class="relative text-xs" :class="{ 'text-red-400': isDebt(account.balance) }">
        <NumberHider />
        {{ formatMoney(account.balance, account.currency_code) }}
        <span v-if="creditLimitDate" class="text-success">
            ({{ formatMoney(availableCredit, account.currency_code) }} )
        </span>
      </p>
    </section>
    <div class="hidden group-hover:flex transaction">
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

<style scoped lang="scss">


</style>

