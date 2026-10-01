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
  index: number,
  cutInfo?: { days_since_cut: number | null; cut_at: string | null; statement_unpaid: boolean }
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

// Days since the last UNPAID statement cut, fed from the cycle-aware summary
// (see CreditCardsLedger). Null when the statement is settled, so the badge
// only shows on cards with an open, unpaid cut.
const cutDays = computed<number | null>(() => {
    const c = props.cutInfo;
    if (!c || !c.statement_unpaid || c.days_since_cut === null || c.days_since_cut === undefined) return null;
    return c.days_since_cut;
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
    return account.value.credit_closing_day ? ` - ${account.value.credit_closing_day}${suffixes.get(formatter.select(Number(account.value.credit_closing_day)))}` : '';
})

const renewalNotice = computed(() => {
    const month = Number(account.value.credit_renewal_month);
    if (!month) return null;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    let renewalStart = new Date(today.getFullYear(), month - 1, 1);
    const renewalEnd = new Date(today.getFullYear(), month, 0);
    if (today > renewalEnd) renewalStart = new Date(today.getFullYear() + 1, month - 1, 1);

    const daysUntil = Math.max(0, Math.ceil((renewalStart.getTime() - today.getTime()) / 86_400_000));
    return daysUntil <= 30 ? { daysUntil } : null;
});


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
    class="card w-full rounded-lg border border-base-lvl-2 bg-base-lvl-3 p-4 cursor-pointer hover:border-base-lvl-2 transition-all relative overflow-hidden"
    :class="{ 'border-primary border-2 ring-1 ring-primary': isSelected }"
    ref="CreditCardView"
    v-bind="$attrs"
    v-on="$attrs"
    :title="account.balance"
    :data-slide="index"
  >
    <!-- Header -->
    <div class="flex justify-between items-start gap-2 mb-3 pb-3 border-b border-base-lvl-2">
      <div class="flex-1 min-w-0">
        <p class="text-xs text-body-1 font-medium truncate">{{ account.name }}</p>
      </div>

      <!-- Status Indicator -->
      <div class="flex-shrink-0 flex items-center gap-2">
        <AccountReconciliationAlert v-if="hasPendingReconciliation" />
        <div v-else-if="isReconciled" class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
          <IMdiCheckCircle class="text-green-600 text-xs" />
        </div>
         <div class="flex gap-1">
        <button
          class="p-1.5 text-body-1/70 hover:text-primary hover:bg-primary/5 rounded transition-colors flex-shrink-0"
          @click.stop="$emit('edit')"
          title="Edit account"
        >
          <IMdiEdit class="w-4 h-4" />
        </button>
        <button
          class="p-1.5 text-body-1/70 hover:text-primary hover:bg-primary/5 rounded transition-colors flex-shrink-0"
          @click.stop="$emit('link')"
          title="Link account"
        >
          <IMdiLink class="w-4 h-4" />
        </button>
      </div>
      </div>
    </div>

    <!-- Balance Section -->
    <div class="mb-3">
      <p class="text-xs text-body-1 mb-0.5">Current Balance</p>
      <p class="relative text-base font-bold text-body">
        <NumberHider />
        {{ formatMoney(account.balance, account.currency_code) }}
        / {{ formatMoney(availableCredit, account.currency_code) }}
      </p>
    </div>

    <div v-if="cutDays !== null" class="flex items-center gap-2 pt-3 border-t border-base-lvl-2 text-xs text-error">
      <i class="fa fa-scissors" />
      <span class="font-medium">
        {{ cutDays === 0 ? $t('Cut today · unpaid') : $t('Cut {days}d ago · unpaid', { days: cutDays }) }}
      </span>
    </div>

    <div v-if="renewalNotice" class="flex items-center gap-2 pt-3 border-t border-base-lvl-2 text-xs text-warning">
      <i class="fa fa-calendar" />
      <span class="font-medium">
        {{ renewalNotice.daysUntil === 0 ? $t('Renewal this month') : $t('Renewal in {days} days', { days: renewalNotice.daysUntil }) }}
      </span>
      <span v-if="account.credit_annual_fee" class="text-body-1/70">
        · {{ formatMoney(account.credit_annual_fee, account.currency_code) }}
      </span>
    </div>
  </div>
</template>

<style scoped lang="scss">


</style>

