<template>
  <Modal
    :show="show"
    max-width="4xl"
    :full-height="fullHeight"
    :closeable="closeable"
    @close="$emit('close')"
  >
    <div class="flex-1 pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header v-if="fullHeight" class="pb-4 text-lg font-bold border-b">
          {{ isMultiCurrency ? 'Multi-Currency Transaction' : 'Create Transaction' }}
        </header>
        
        <TransactionTypesPicker v-model="form.direction" />

        <div class="mt-2">
          <div class="px-4 md:flex md:space-x-2 md:px-0">
            <AtField
              label="Date"
              class="flex justify-between w-full md:w-4/12 md:block"
            >
              <NDatePicker
                v-model:value="form.date"
                type="date"
                size="large"
                class="w-48 md:w-full"
              />
            </AtField>

            <AtField
              label="Description"
              class="flex justify-between w-full space-x-2 md:w-8/12 md:block md:space-x-0"
            >
              <LogerInput v-model="form.description" class="w-48 md:w-full" />
            </AtField>
          </div>

          <!-- Multi-Currency Transaction Entry -->
          <div v-if="isMultiCurrency" class="px-4 md:px-0 mt-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <h4 class="font-semibold text-blue-800 mb-3">Multi-Currency Transaction</h4>
              
              <!-- Currency Selection -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <AtField label="Transaction Currency">
                  <CurrencySelector
                    v-model="transactionCurrency"
                    :exclude-currencies="[]"
                    @change="handleTransactionCurrencyChange"
                  />
                </AtField>
                
                <AtField label="Account" v-if="!isTransfer">
                  <NSelect
                    v-model:value="selectedAccountId"
                    :options="multiCurrencyAccountOptions"
                    placeholder="Select multi-currency account"
                    @update:value="handleAccountChange"
                  />
                </AtField>
              </div>

              <!-- Amount Entry -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AtField label="Amount">
                  <MultiCurrencyInput
                    v-model="currencyAmount"
                    :target-currency="accountPrimaryCurrency"
                    :exchange-rate="currentExchangeRate"
                    :show-exchange-info="showConversionInfo"
                    @currency-change="handleTransactionCurrencyChange"
                  />
                </AtField>
                
                <!-- Exchange Rate Display/Input -->
                <div v-if="showConversionInfo && needsConversion">
                  <AtField label="Exchange Rate (Optional)">
                    <div class="space-y-2">
                      <LogerInput
                        v-model="manualExchangeRate"
                        type="number"
                        step="0.0001"
                        placeholder="Auto-calculated on payment"
                        @update:model-value="handleManualRateChange"
                      />
                      <div class="text-xs text-gray-500">
                        1 {{ transactionCurrency }} = {{ displayExchangeRate }} {{ accountPrimaryCurrency }}
                      </div>
                    </div>
                  </AtField>
                </div>
              </div>

              <!-- Conversion Preview -->
              <div v-if="showConversionInfo && needsConversion" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                <div class="text-sm">
                  <div class="font-medium text-yellow-800">Conversion Preview:</div>
                  <div class="text-yellow-700">
                    {{ formatCurrency(currencyAmount.amount, transactionCurrency) }} 
                    will be recorded as pending until payment
                  </div>
                  <div v-if="currentExchangeRate" class="text-yellow-700">
                    Estimated value: {{ formatCurrency(convertedAmount, accountPrimaryCurrency) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Standard Transaction Items -->
          <TransactionItems
            ref="gridSplitsRef"
            :items="splits"
            :is-transfer="isTransfer"
            :mode="form.direction"
            :categories="categoryOptions"
            :accounts="accountOptions"
            :full-height="fullHeight"
            :currency-override="isMultiCurrency ? transactionCurrency : null"
          />
        </div>
      </div>
    </div>

    <footer class="flex items-center justify-between w-full px-6 py-4 space-x-3 bg-base-lvl-2">
      <section class="flex">
        <div class="flex items-center space-x-2">
          <AtFieldCheck 
            v-if="hasMultiCurrencyAccounts" 
            v-model="isMultiCurrency" 
            label="Multi-currency transaction" 
          />
        </div>
      </section>
      
      <div class="flex space-x-2">
        <LogerButton @click="$emit('close')" rounded class="h-10 text-body" :disabled="form.processing">
          Cancel
        </LogerButton>
        <LogerButton
          class="h-10 text-white capitalize bg-primary"
          :processing="form.processing"
          :disabled="form.processing || !canSave"
          @click="onSubmit()" 
          rounded
        >
          {{ saveText }}
        </LogerButton>
      </div>
    </footer>
  </Modal>
</template>

<script setup lang="ts">
import { computed, ref, watch, inject, nextTick } from 'vue';
import { format, parseISO } from 'date-fns';
import { AtField, AtFieldCheck } from 'atmosphere-ui';
import { NSelect, NDatePicker } from 'naive-ui';

import Modal from '@/Components/atoms/Modal.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import TransactionTypesPicker from './TransactionTypesPicker.vue';
import TransactionItems from './TransactionItems.vue';
import CurrencySelector from './CurrencySelector.vue';
import MultiCurrencyInput from './MultiCurrencyInput.vue';

import { TRANSACTION_DIRECTIONS } from '@/domains/transactions';
import { useInertiaForm, validators } from '@/utils/useInertiaForm';
import { formatCurrency } from '../currency-constants';
import { calculateExchangeRate, convertAmount } from '../multi-currency-utils';

interface Props {
  show: boolean;
  maxWidth?: string;
  closeable?: boolean;
  categories?: any[];
  accounts?: any[];
  transactionData?: any;
  mode?: string;
  fullHeight?: boolean;
}

interface Emits {
  (e: 'close'): void;
  (e: 'saved', transaction: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  maxWidth: '4xl',
  closeable: true,
  categories: () => [],
  accounts: () => [],
  transactionData: () => ({}),
  mode: 'income',
  fullHeight: false
});

const emit = defineEmits<Emits>();

const form = useInertiaForm({
  name: '',
  payee_id: '',
  payee_label: '',
  label_id: '',
  label_name: '',
  date: new Date(),
  is_transfer: false,
  description: '',
  direction: 'WITHDRAW',
  category_id: null,
  counter_account_id: null,
  account_id: null,
  total: 0,
  has_splits: false,
  // Multi-currency fields
  currency_code: 'USD',
  exchange_rate: null,
  exchange_amount: null,
  is_multi_currency: false
});

form.validationSchema({
  description: [validators.isRequired],
});

const splits = ref<Record<string, any>[]>([]);
const gridSplitsRef = ref();

// Multi-currency state
const isMultiCurrency = ref(false);
const transactionCurrency = ref('USD');
const selectedAccountId = ref(null);
const manualExchangeRate = ref(null);
const currentExchangeRate = ref(null);

const currencyAmount = ref({
  amount: 0,
  currency: 'USD'
});

// Computed properties
const categoryOptions = inject('categoryOptions', []);
const accountOptions = inject('accountsOptions', []);

const multiCurrencyAccounts = computed(() => {
  return props.accounts.filter(account => account.is_multi_currency);
});

const hasMultiCurrencyAccounts = computed(() => {
  return multiCurrencyAccounts.value.length > 0;
});

const multiCurrencyAccountOptions = computed(() => {
  return multiCurrencyAccounts.value.map(account => ({
    value: account.id,
    label: `${account.name} (${account.currency_code})`,
    currency_code: account.currency_code,
    secondary_currencies: account.secondary_currencies || []
  }));
});

const selectedAccount = computed(() => {
  return multiCurrencyAccounts.value.find(account => account.id === selectedAccountId.value);
});

const accountPrimaryCurrency = computed(() => {
  return selectedAccount.value?.currency_code || 'USD';
});

const needsConversion = computed(() => {
  return transactionCurrency.value !== accountPrimaryCurrency.value;
});

const showConversionInfo = computed(() => {
  return isMultiCurrency.value && selectedAccount.value && needsConversion.value;
});

const convertedAmount = computed(() => {
  if (currentExchangeRate.value && currencyAmount.value.amount) {
    return convertAmount(currencyAmount.value.amount, currentExchangeRate.value);
  }
  return 0;
});

const displayExchangeRate = computed(() => {
  return currentExchangeRate.value ? currentExchangeRate.value.toFixed(4) : 'TBD';
});

const isTransfer = computed(() => {
  return form.is_transfer;
});

const canSave = computed(() => {
  if (isMultiCurrency.value) {
    return form.description && selectedAccountId.value && currencyAmount.value.amount > 0;
  }
  return form.description;
});

const saveText = computed(() => {
  return !props.transactionData?.id ? 'Save' : 'Update';
});

// Methods
const handleTransactionCurrencyChange = (currency: string) => {
  transactionCurrency.value = currency;
  currencyAmount.value.currency = currency;
  
  // Reset manual exchange rate when currency changes
  manualExchangeRate.value = null;
  currentExchangeRate.value = null;
};

const handleAccountChange = (accountId: number) => {
  selectedAccountId.value = accountId;
  form.account_id = accountId;
  
  // Reset exchange rate when account changes
  manualExchangeRate.value = null;
  currentExchangeRate.value = null;
};

const handleManualRateChange = (rate: number) => {
  currentExchangeRate.value = rate;
};

const resetSplits = (lastSaved: Record<string, any>) => {
  splits.value = [{
    payee_id: '',
    payee_label: '',
    label_id: '',
    label_name: '',
    category_id: '',
    category: '',
    counter_account_id: '',
    account_id: lastSaved.account_id,
    amount: 0,
  }];
};

const onSubmit = () => {
  const splitItems = gridSplitsRef.value.getSplits();

  if (!splitItems.every((split: Record<string, string>) => split.amount)) {
    alert("Every split must have an amount");
    return;
  }

  const actions = {
    save: {
      method: "post",
      url: () => route("transactions.store"),
    },
    update: {
      method: "put",
      url: () => route("transactions.update", props.transactionData),
    },
  };

  const method = props.transactionData && props.transactionData.id ? "update" : "save";
  const action = actions[method];

  form.transform((formData) => {
    const data = {
      ...formData,
      resource_type_id: "MANUAL",
      date: format(new Date(formData.date), "yyyy-MM-dd"),
      status: "verified",
      direction: formData.is_transfer ? TRANSACTION_DIRECTIONS.WITHDRAW : formData.direction,
      category_id: formData.is_transfer ? null : formData.category_id,
    };

    if (isMultiCurrency.value) {
      // Multi-currency transaction data
      data.currency_code = transactionCurrency.value;
      data.total = currencyAmount.value.amount;
      data.is_multi_currency = true;
      data.account_id = selectedAccountId.value;
      
      // Only set exchange rate if manually provided
      if (manualExchangeRate.value) {
        data.exchange_rate = manualExchangeRate.value;
        data.exchange_amount = convertAmount(currencyAmount.value.amount, manualExchangeRate.value);
      }
    } else {
      // Standard transaction
      const splitItem = splitItems[0];
      data.category_id = splitItem.category_id;
      data.label_id = splitItem.label_id;
      data.payee_id = splitItem.payee_id;
      data.counter_account_id = formData.is_transfer ? splitItem.counter_account_id : null;
      data.account_id = splitItem.account_id;
      data.total = splitItem.amount;
    }

    data.has_splits = splitItems?.length > 1;
    if (data.has_splits) {
      data.items = splitItems;
    }

    return data;
  })
  .submit(action.method, action.url(), {
    preserveScroll: true,
    onSuccess: (response) => {
      emit('saved', response.data);
      emit('close');
      form.reset();
    },
  });
};

// Watchers
watch(() => props.transactionData, (newValue) => {
  if (newValue && Object.keys(newValue).length > 0) {
    Object.keys(form.data()).forEach((field) => {
      const fieldData = newValue[field];
      if (field === "date" && newValue[field]) {
        form[field] = parseISO(fieldData);
      } else if (field === 'is_transfer') {
        form[field] = Boolean(fieldData);
      } else {
        form[field] = fieldData || form[field];
      }
    });

    // Set multi-currency data if available
    if (newValue.is_multi_currency) {
      isMultiCurrency.value = true;
      transactionCurrency.value = newValue.currency_code || 'USD';
      selectedAccountId.value = newValue.account_id;
      currencyAmount.value = {
        amount: newValue.total || 0,
        currency: newValue.currency_code || 'USD'
      };
      
      if (newValue.exchange_rate) {
        manualExchangeRate.value = newValue.exchange_rate;
        currentExchangeRate.value = newValue.exchange_rate;
      }
    }

    if (isTransfer.value) {
      form.direction = TRANSACTION_DIRECTIONS.TRANSFER;
    }

    // Set up splits
    if (newValue.has_splits) {
      splits.value = props.transactionData?.splits ?? props.transactionData.lines?.filter((line: any) => line.is_split);
    } else {
      const anchorLine = newValue.lines?.find(item => item.anchor);
      splits.value = [{
        payee_id: newValue.payee_id ?? newValue.payee?.id,
        payee_label: newValue.payee?.name,
        label_id: newValue.label_id ?? anchorLine?.labels?.at?.(0)?.id,
        label_name: anchorLine?.labels?.at?.(0)?.name,
        category_id: newValue.category_id ?? newValue.category?.id,
        category: newValue.category,
        counter_account_id: newValue.counter_account_id ?? newValue.counter_account?.id,
        account_id: newValue.account_id ?? newValue.account?.id,
        account: newValue.account,
        amount: newValue.total,
      }];
    }
  }
}, { deep: true });

watch(() => props.show, (show) => {
  if (show && !props.transactionData?.id) {
    form.direction = props.mode?.toUpperCase() ?? "WITHDRAW";
    isMultiCurrency.value = false;
    transactionCurrency.value = 'USD';
    selectedAccountId.value = null;
    manualExchangeRate.value = null;
    currentExchangeRate.value = null;
    currencyAmount.value = { amount: 0, currency: 'USD' };
  } else if (!show) {
    form.reset();
  }
});

watch(() => form.direction, (direction) => {
  if (direction?.toLowerCase() === "transfer") {
    form.is_transfer = true;
  } else {
    form.is_transfer = false;
  }
});
</script>

<style scoped>
.multi-currency-section {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}
</style>