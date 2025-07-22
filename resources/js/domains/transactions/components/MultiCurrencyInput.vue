<template>
  <div class="multi-currency-input">
    <div class="flex space-x-2">
      <div class="flex-1">
        <InputMoney
          v-model="amount"
          :disabled="disabled"
          :placeholder="amountPlaceholder"
          @update:model-value="handleAmountChange"
        />
      </div>
      <div class="w-32">
        <CurrencySelector
          v-model="currency"
          :disabled="disabled"
          :exclude-currencies="excludeCurrencies"
          @change="handleCurrencyChange"
        />
      </div>
    </div>
    
    <!-- Exchange rate display -->
    <div v-if="showExchangeInfo && exchangeRate" class="mt-2 text-sm text-gray-600">
      <div class="flex items-center justify-between">
        <span>Exchange Rate:</span>
        <span>1 {{ currency }} = {{ exchangeRate.toFixed(4) }} {{ targetCurrency }}</span>
      </div>
      <div v-if="convertedAmount" class="flex items-center justify-between">
        <span>Converted Amount:</span>
        <span>{{ formatCurrency(convertedAmount, targetCurrency) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import InputMoney from '@/Components/atoms/InputMoney.vue';
import CurrencySelector from './CurrencySelector.vue';
import { formatCurrency } from '../currency-constants';
import { calculateExchangeRate, convertAmount } from '../multi-currency-utils';

interface Props {
  modelValue?: {
    amount: number;
    currency: string;
  };
  disabled?: boolean;
  amountPlaceholder?: string;
  excludeCurrencies?: string[];
  showExchangeInfo?: boolean;
  targetCurrency?: string;
  exchangeRate?: number;
}

interface Emits {
  (e: 'update:modelValue', value: { amount: number; currency: string }): void;
  (e: 'amountChange', amount: number): void;
  (e: 'currencyChange', currency: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  amountPlaceholder: 'Enter amount',
  excludeCurrencies: () => [],
  showExchangeInfo: false,
  targetCurrency: 'USD'
});

const emit = defineEmits<Emits>();

const amount = ref(props.modelValue?.amount || 0);
const currency = ref(props.modelValue?.currency || 'USD');

const convertedAmount = computed(() => {
  if (props.exchangeRate && amount.value) {
    return convertAmount(amount.value, props.exchangeRate);
  }
  return 0;
});

const handleAmountChange = (newAmount: number) => {
  amount.value = newAmount;
  emit('update:modelValue', { amount: newAmount, currency: currency.value });
  emit('amountChange', newAmount);
};

const handleCurrencyChange = (newCurrency: any) => {
  if (newCurrency) {
    currency.value = newCurrency.code;
    emit('update:modelValue', { amount: amount.value, currency: newCurrency.code });
    emit('currencyChange', newCurrency.code);
  }
};

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    amount.value = newValue.amount;
    currency.value = newValue.currency;
  }
}, { deep: true });
</script>

<style scoped>
.multi-currency-input {
  min-width: 200px;
}
</style>