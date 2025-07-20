<template>
  <Modal :show="show" max-width="3xl" :closeable="closeable" @close="$emit('close')">
    <div class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header class="py-3 border-b">
          <h3 class="text-lg font-bold">Multi-Currency Payment</h3>
          <p class="text-sm text-gray-600 mt-1">
            Process payment with currency conversion for {{ account?.name }}
          </p>
        </header>

        <div class="px-4 pt-5 mt-2 space-y-6">
          <!-- Account Summary -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-semibold mb-3">Account Summary</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="text-sm text-gray-600">Primary Currency Balance</div>
                <div class="text-lg font-semibold">
                  {{ formatCurrency(account?.primary_balance || 0, account?.currency_code) }}
                </div>
              </div>
              <div v-if="pendingBalances.length > 0">
                <div class="text-sm text-gray-600">Pending Secondary Currency Balances</div>
                <div class="space-y-1">
                  <div 
                    v-for="balance in pendingBalances" 
                    :key="balance.currency_code"
                    class="text-sm font-medium"
                  >
                    {{ formatCurrency(balance.pending_balance, balance.currency_code) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Details -->
          <div class="space-y-4">
            <h4 class="font-semibold">Payment Details</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <AtField label="Payment Date">
                <NDatePicker
                  v-model:value="form.payment_date"
                  type="date"
                  size="large"
                  class="w-full"
                />
              </AtField>
              
              <AtField label="Payment Method">
                <NSelect
                  v-model:value="form.payment_method_id"
                  :options="paymentMethodOptions"
                  placeholder="Select payment method"
                  class="w-full"
                />
              </AtField>
            </div>

            <AtField label="Description">
              <LogerInput 
                v-model="form.description" 
                placeholder="Payment description"
                class="w-full" 
              />
            </AtField>
          </div>

          <!-- Currency Conversion Section -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-semibold text-blue-800 mb-4">Currency Conversion</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <!-- Secondary Currency Amount -->
              <AtField label="Secondary Currency Amount">
                <div class="space-y-2">
                  <div class="flex space-x-2">
                    <div class="flex-1">
                      <LogerInput
                        v-model="form.total"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        @update:model-value="calculateExchangeRate"
                      />
                    </div>
                    <div class="w-24">
                      <CurrencySelector
                        v-model="form.currency_code"
                        :exclude-currencies="[account?.currency_code]"
                        @change="handleSecondaryCurrencyChange"
                      />
                    </div>
                  </div>
                </div>
              </AtField>

              <!-- Primary Currency Amount -->
              <AtField :label="`Primary Currency Amount (${account?.currency_code})`">
                <LogerInput
                  v-model="form.exchange_amount"
                  type="number"
                  step="0.01"
                  placeholder="0.00"
                  @update:model-value="calculateExchangeRate"
                />
              </AtField>
            </div>

            <!-- Exchange Rate Display -->
            <div v-if="calculatedExchangeRate" class="bg-white rounded p-3 border">
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium">Calculated Exchange Rate:</span>
                <span class="font-semibold">
                  1 {{ form.currency_code }} = {{ calculatedExchangeRate.toFixed(4) }} {{ account?.currency_code }}
                </span>
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Rate calculated from provided amounts
              </div>
            </div>

            <!-- Conversion Preview -->
            <div v-if="form.total && form.exchange_amount" class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
              <div class="text-sm">
                <div class="font-medium text-green-800">Payment Summary:</div>
                <div class="text-green-700">
                  Paying {{ formatCurrency(form.total, form.currency_code) }} 
                  = {{ formatCurrency(form.exchange_amount, account?.currency_code) }}
                </div>
                <div class="text-green-700">
                  This will reduce the pending {{ form.currency_code }} balance and update the primary balance
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Account Selection -->
          <AtField label="Payment Account">
            <NSelect
              v-model:value="form.payment_account_id"
              :options="paymentAccountOptions"
              placeholder="Select account to pay from"
              class="w-full"
            />
          </AtField>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between w-full px-6 py-4 bg-base-lvl-2">
      <div class="text-sm text-gray-500">
        <span v-if="calculatedExchangeRate">
          Exchange rate will be recorded for future reference
        </span>
      </div>
      
      <div class="flex items-center space-x-2">
        <AtButton 
          @click="$emit('close')"
          type="secondary"
          :disabled="processing"
        >
          Cancel
        </AtButton>
        
        <AtButton 
          @click="processPayment"
          type="primary"
          :disabled="!canProcessPayment || processing"
          class="text-white bg-primary"
        >
          {{ processing ? 'Processing...' : 'Process Payment' }}
        </AtButton>
      </div>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { AtField, AtButton } from 'atmosphere-ui';
import { NSelect, NDatePicker } from 'naive-ui';

import Modal from '@/Components/atoms/Modal.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';
import CurrencySelector from './CurrencySelector.vue';

import { formatCurrency } from '../currency-constants';
import { calculateExchangeRate as calcRate } from '../multi-currency-utils';
import { paymentMethods } from '../constants';

interface Props {
  show: boolean;
  closeable?: boolean;
  account?: any;
  transaction?: any;
}

interface Emits {
  (e: 'close'): void;
  (e: 'saved', payment: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  closeable: true
});

const emit = defineEmits<Emits>();

const processing = ref(false);
const calculatedExchangeRate = ref(0);

const form = useForm({
  payment_date: new Date(),
  payment_method_id: 'cash',
  description: '',
  total: 0,
  exchange_amount: 0,
  currency_code: 'USD',
  exchange_rate: 0,
  payment_account_id: null,
  account_id: null
});

// Computed properties
const paymentMethodOptions = computed(() => {
  return paymentMethods.map(method => ({
    value: method.id,
    label: method.name
  }));
});

const paymentAccountOptions = computed(() => {
  // This would typically come from props or be fetched
  // For now, return empty array - should be populated with available accounts
  return [];
});

const pendingBalances = computed(() => {
  return props.account?.currency_balances?.filter(balance => 
    balance.pending_balance && balance.pending_balance > 0
  ) || [];
});

const canProcessPayment = computed(() => {
  return form.total > 0 && 
         form.exchange_amount > 0 && 
         form.currency_code && 
         form.payment_account_id &&
         calculatedExchangeRate.value > 0;
});

// Methods
const calculateExchangeRate = () => {
  if (form.total && form.exchange_amount) {
    calculatedExchangeRate.value = calcRate(form.total, form.exchange_amount);
    form.exchange_rate = calculatedExchangeRate.value;
  } else {
    calculatedExchangeRate.value = 0;
    form.exchange_rate = 0;
  }
};

const handleSecondaryCurrencyChange = (currency: any) => {
  if (currency) {
    form.currency_code = currency.code;
    calculateExchangeRate();
  }
};

const processPayment = () => {
  if (!canProcessPayment.value) return;
  
  processing.value = true;
  form.account_id = props.account?.id;
  
  const paymentData = {
    ...form.data(),
    payment_date: format(form.payment_date, 'yyyy-MM-dd'),
    exchange_rate: calculatedExchangeRate.value
  };

  form.transform(() => paymentData)
    .post(route('multi-currency.payments.store'), {
      onSuccess: (response) => {
        emit('saved', response.data);
        emit('close');
        resetForm();
      },
      onError: (errors) => {
        console.error('Payment processing failed:', errors);
      },
      onFinish: () => {
        processing.value = false;
      }
    });
};

const resetForm = () => {
  form.reset();
  calculatedExchangeRate.value = 0;
  form.payment_date = new Date();
  form.payment_method_id = 'cash';
};

// Watchers
watch(() => props.show, (show) => {
  if (show) {
    form.account_id = props.account?.id;
    
    // Pre-fill with transaction data if provided
    if (props.transaction) {
      form.total = props.transaction.total;
      form.currency_code = props.transaction.currency_code;
      form.description = `Payment for ${props.transaction.description}`;
    }
    
    // Set default secondary currency from pending balances
    if (pendingBalances.value.length > 0) {
      form.currency_code = pendingBalances.value[0].currency_code;
    }
  } else {
    resetForm();
  }
});

watch(() => [form.total, form.exchange_amount], () => {
  calculateExchangeRate();
});
</script>

<style scoped>
.payment-summary {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
}
</style>