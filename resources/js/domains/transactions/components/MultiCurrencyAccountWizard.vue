<template>
  <Modal :show="show" max-width="3xl" :closeable="closeable" @close="$emit('close')">
    <div class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header class="py-3 border-b">
          <h3 class="text-lg font-bold">Multi-Currency Account Setup</h3>
        </header>

        <div class="px-4 pt-5 mt-2">
          <!-- Step 1: Basic Account Info -->
          <div v-if="currentStep === 1" class="space-y-4">
            <h4 class="text-md font-semibold mb-4">Step 1: Basic Account Information</h4>
            
            <AtField label="Account Name" class="flex justify-between w-full space-x-4 md:space-x-0">
              <LogerInput v-model="form.name" class="w-48 md:w-full" placeholder="Enter account name" />
            </AtField>

            <AtField label="Account Type" class="flex justify-between w-full space-x-4 md:space-x-0">
              <NSelect 
                v-model:value="form.account_detail_type_id"
                :options="detailOptions"
                placeholder="Select account type"
                class="w-48 md:w-full"
              />
            </AtField>

            <AtField label="Primary Currency" class="flex justify-between w-full space-x-4 md:space-x-0">
              <CurrencySelector
                v-model="form.currency_code"
                placeholder="Select primary currency"
                :clearable="false"
                class="w-48 md:w-full"
              />
            </AtField>

            <AtFieldCheck 
              label="Enable multi-currency support" 
              v-model="form.is_multi_currency"
            />
          </div>

          <!-- Step 2: Secondary Currencies -->
          <div v-if="currentStep === 2" class="space-y-4">
            <h4 class="text-md font-semibold mb-4">Step 2: Secondary Currencies</h4>
            
            <div class="mb-4">
              <p class="text-sm text-gray-600 mb-3">
                Select additional currencies this account will support. Transactions can be recorded in these currencies.
              </p>
              
              <div class="space-y-3">
                <div class="flex items-center space-x-2">
                  <CurrencySelector
                    v-model="newSecondaryCurrency"
                    :exclude-currencies="[form.currency_code, ...form.secondary_currencies]"
                    placeholder="Add secondary currency"
                    class="flex-1"
                  />
                  <AtButton 
                    @click="addSecondaryCurrency"
                    :disabled="!newSecondaryCurrency"
                    type="primary"
                    size="small"
                  >
                    Add
                  </AtButton>
                </div>

                <!-- Selected secondary currencies -->
                <div v-if="form.secondary_currencies.length > 0" class="mt-4">
                  <h5 class="text-sm font-medium mb-2">Selected Secondary Currencies:</h5>
                  <div class="space-y-2">
                    <div 
                      v-for="(currency, index) in form.secondary_currencies" 
                      :key="currency"
                      class="flex items-center justify-between p-2 bg-gray-50 rounded"
                    >
                      <span>{{ getCurrencyDisplay(currency) }}</span>
                      <AtButton 
                        @click="removeSecondaryCurrency(index)"
                        type="secondary"
                        size="small"
                        class="text-danger"
                      >
                        Remove
                      </AtButton>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Credit Card Specific Settings -->
          <div v-if="currentStep === 3 && isCreditCard" class="space-y-4">
            <h4 class="text-md font-semibold mb-4">Step 3: Credit Card Settings</h4>
            
            <AtField label="Closing Day" class="flex justify-between w-full space-x-4 md:space-x-0">
              <LogerInput 
                v-model="form.credit_closing_day" 
                type="number" 
                min="1" 
                max="31"
                class="w-48 md:w-full" 
                placeholder="Day of month (1-31)"
              />
            </AtField>

            <AtField label="Credit Limit" class="flex justify-between w-full space-x-4 md:space-x-0">
              <MultiCurrencyInput
                v-model="creditLimitInput"
                :target-currency="form.currency_code"
                class="w-48 md:w-full"
              />
            </AtField>
          </div>

          <!-- Step 4: Review -->
          <div v-if="currentStep === 4" class="space-y-4">
            <h4 class="text-md font-semibold mb-4">Step 4: Review Configuration</h4>
            
            <div class="bg-gray-50 p-4 rounded space-y-3">
              <div><strong>Account Name:</strong> {{ form.name }}</div>
              <div><strong>Primary Currency:</strong> {{ getCurrencyDisplay(form.currency_code) }}</div>
              <div><strong>Multi-Currency:</strong> {{ form.is_multi_currency ? 'Enabled' : 'Disabled' }}</div>
              
              <div v-if="form.secondary_currencies.length > 0">
                <strong>Secondary Currencies:</strong>
                <ul class="list-disc list-inside ml-4">
                  <li v-for="currency in form.secondary_currencies" :key="currency">
                    {{ getCurrencyDisplay(currency) }}
                  </li>
                </ul>
              </div>
              
              <div v-if="isCreditCard">
                <strong>Credit Card Settings:</strong>
                <ul class="list-disc list-inside ml-4">
                  <li v-if="form.credit_closing_day">Closing Day: {{ form.credit_closing_day }}</li>
                  <li v-if="form.credit_limit">Credit Limit: {{ formatCurrency(form.credit_limit, form.currency_code) }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with navigation -->
    <div class="flex items-center justify-between w-full px-6 py-4 bg-base-lvl-2">
      <div class="flex space-x-2">
        <AtButton 
          v-if="currentStep > 1"
          @click="previousStep"
          type="secondary"
          :disabled="processing"
        >
          Previous
        </AtButton>
      </div>
      
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-500">Step {{ currentStep }} of {{ totalSteps }}</span>
        
        <AtButton 
          v-if="currentStep < totalSteps"
          @click="nextStep"
          type="primary"
          :disabled="!canProceed || processing"
        >
          Next
        </AtButton>
        
        <AtButton 
          v-if="currentStep === totalSteps"
          @click="submit"
          type="primary"
          :disabled="processing"
          class="text-white bg-primary"
        >
          Create Account
        </AtButton>
        
        <AtButton 
          @click="$emit('close')"
          type="secondary"
          :disabled="processing"
        >
          Cancel
        </AtButton>
      </div>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { NSelect } from 'naive-ui';
import { AtField, AtFieldCheck, AtButton } from 'atmosphere-ui';

import Modal from '@/Components/atoms/Modal.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';
import CurrencySelector from './CurrencySelector.vue';
import MultiCurrencyInput from './MultiCurrencyInput.vue';

import { makeOptions } from '@/utils/naiveui';
import { getCurrencyByCode, formatCurrency } from '../currency-constants';

interface Props {
  show: boolean;
  closeable?: boolean;
  formData?: any;
}

interface Emits {
  (e: 'close'): void;
  (e: 'saved', account: any): void;
}

const props = withDefaults(defineProps<Props>(), {
  closeable: true,
  formData: () => ({})
});

const emit = defineEmits<Emits>();

const currentStep = ref(1);
const newSecondaryCurrency = ref('');
const processing = ref(false);

const creditLimitInput = ref({
  amount: 0,
  currency: 'USD'
});

const form = useForm({
  name: '',
  account_detail_type_id: null,
  currency_code: 'USD',
  is_multi_currency: false,
  secondary_currencies: [] as string[],
  credit_closing_day: null,
  credit_limit: null,
  currency_config: {}
});

const detailTypes = usePage().props.accountDetailTypes;
const detailOptions = ref(makeOptions(detailTypes, ["id", "label"]));

const creditCard = computed(() => {
  return detailOptions.value.find((type) => type.label.toLowerCase() == "credit cards");
});

const isCreditCard = computed(() => {
  return form.account_detail_type_id == creditCard.value?.value;
});

const totalSteps = computed(() => {
  return isCreditCard.value ? 4 : (form.is_multi_currency ? 3 : 2);
});

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      return form.name && form.account_detail_type_id && form.currency_code;
    case 2:
      return true; // Secondary currencies are optional
    case 3:
      return !isCreditCard.value || (form.credit_closing_day && form.credit_limit);
    case 4:
      return true;
    default:
      return false;
  }
});

const getCurrencyDisplay = (currencyCode: string) => {
  const currency = getCurrencyByCode(currencyCode);
  return currency ? `${currency.code} - ${currency.name}` : currencyCode;
};

const addSecondaryCurrency = () => {
  if (newSecondaryCurrency.value && !form.secondary_currencies.includes(newSecondaryCurrency.value)) {
    form.secondary_currencies.push(newSecondaryCurrency.value);
    newSecondaryCurrency.value = '';
  }
};

const removeSecondaryCurrency = (index: number) => {
  form.secondary_currencies.splice(index, 1);
};

const nextStep = () => {
  if (currentStep.value === 1 && !form.is_multi_currency && !isCreditCard.value) {
    currentStep.value = 4; // Skip to review if no multi-currency and not credit card
  } else if (currentStep.value === 2 && !isCreditCard.value) {
    currentStep.value = 4; // Skip credit card settings if not credit card
  } else if (currentStep.value < totalSteps.value) {
    currentStep.value++;
  }
};

const previousStep = () => {
  if (currentStep.value === 4 && !isCreditCard.value && !form.is_multi_currency) {
    currentStep.value = 1; // Skip back to step 1 if no multi-currency and not credit card
  } else if (currentStep.value === 4 && !isCreditCard.value) {
    currentStep.value = 2; // Skip back to step 2 if not credit card
  } else if (currentStep.value > 1) {
    currentStep.value--;
  }
};

const submit = () => {
  processing.value = true;
  
  // Prepare form data
  const formData = {
    ...form.data(),
    credit_limit: creditLimitInput.value.amount,
    currency_config: {
      primary_currency: form.currency_code,
      secondary_currencies: form.secondary_currencies,
      multi_currency_enabled: form.is_multi_currency
    }
  };

  form.transform(() => formData)
    .post(route('accounts.store'), {
      onSuccess: (response) => {
        emit('saved', response.data);
        emit('close');
        form.reset();
        currentStep.value = 1;
      },
      onFinish: () => {
        processing.value = false;
      }
    });
};

// Watch for form data changes
watch(() => props.formData, (newValue) => {
  if (newValue && Object.keys(newValue).length > 0) {
    Object.keys(form.data()).forEach((field) => {
      if (newValue[field] !== undefined) {
        form[field] = newValue[field];
      }
    });
    
    if (newValue.secondary_currencies) {
      form.secondary_currencies = [...newValue.secondary_currencies];
    }
    
    if (newValue.credit_limit) {
      creditLimitInput.value = {
        amount: newValue.credit_limit,
        currency: newValue.currency_code || 'USD'
      };
    }
  }
}, { deep: true, immediate: true });

// Watch credit limit input changes
watch(() => creditLimitInput.value, (newValue) => {
  form.credit_limit = newValue.amount;
}, { deep: true });
</script>

<style scoped>
.step-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}
</style>