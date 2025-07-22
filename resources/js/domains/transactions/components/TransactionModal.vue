<script setup lang="ts">
import { format, parseISO } from "date-fns";
import { reactive, toRefs, watch, computed, inject, ref, nextTick } from "vue";
import { AtField, AtFieldCheck, AtInput } from "atmosphere-ui";
import { NSelect, NDatePicker } from "naive-ui";

import Modal from "@/Components/atoms/Modal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import TransactionTypesPicker from "./TransactionTypesPicker.vue";
import TransactionItems from "./TransactionItems.vue";
import CurrencySelector from "./CurrencySelector.vue";
import MultiCurrencyInput from "./MultiCurrencyInput.vue";

import { TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { cloneDeep } from "lodash";
import { ITransaction, ITransactionLine } from "../models";
import { useTransactionStore } from "@/store/transactions";
import { useInertiaForm, validators } from "@/utils/useInertiaForm";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { useStorage } from "@vueuse/core";
import { formatCurrency } from '../currency-constants';
import { calculateExchangeRate, convertAmount } from '../multi-currency-utils';
import { generateRandomColor } from "@/utils";
import axios from "axios";

const props = defineProps({
  show: {
    default: false,
  },
  maxWidth: {
    default: "4xl",
  },
  closeable: {
    default: true,
  },
  categories: {
    type: Array,
    default: [],
  },
  accounts: {
    type: Array,
    default: [],
  },
  recurrence: {
    type: Boolean,
    default: false,
  },
  transactionData: {
    type: Object,
    default: () => ({}),
  },
  mode: {
    type: String,
    default: "income",
  },
  fullHeight: {
    type: Boolean,
  },
});

const emit = defineEmits(["close", "saved"]);

const state = reactive({
  frequencyLabel: "every",
  schedule_settings: {
    end_date: null,
    count: null,
    end_type: "NEVER",
    final_item_date: null,
    interval: 1,
    frequency: "monthly",
    repeat_at_end_of_month: false,
    repeat_on_day_of_month: null,
    start_date: null,
    timezone_id: "America/Santo_Domingo",
  },
  form: useInertiaForm({
    name: "",
    payee_id: "",
    payee_label: "",
    label_id: "",
    label_name: "",
    date: new Date(),
    is_transfer: false,
    description: "",
    direction: "WITHDRAW",
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
  })
});

state.form.validationSchema({
  description: [validators.isRequired],
});

const splits = ref<Record<string, any>[]>([])

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

watch(
  () => state.form.direction,
  (direction) => {
    if (direction?.toLowerCase() == "transfer") {
      state.form.is_transfer = true;
    } else {
      state.form.is_transfer = false;
    }
  }
);

const isTransfer = computed(() => {
  return state.form.is_transfer;
});

const categoryOptions = inject("categoryOptions", []);
const accountOptions = inject("accountsOptions", []);

// Multi-currency computed properties
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

const gridSplitsRef = ref();

watch(
  () => props.transactionData,
  () => {
    const newValue = { ...props.transactionData };
    Object.keys(state.form.data()).forEach((field) => {
      const fieldData = newValue[field]
      if (field == "date" && newValue[field]) {
        state.form[field] = parseISO(fieldData);
      } else if (field == 'is_transfer') {
        state.form[field] = Boolean(fieldData);
      } else {
        state.form[field] = fieldData || state.form[field];
      }
    });

    if (isTransfer.value) {
      state.form.direction = TRANSACTION_DIRECTIONS.TRANSFER;
    }

    if (newValue.has_splits) {
      splits.value = props.transactionData?.splits ?? props.transactionData.lines?.filter((line: ITransactionLine) => line.is_split)
    } else {
      const anchorLine = newValue.lines?.find(item => item.anchor)
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
  },
  { deep: true }
);

watch(
  () => props.show,
  (show) => {
    if (show && !props.transactionData?.id) {
      state.form.direction = props.mode?.toUpperCase() ?? "WITHDRAW";
    } else if (!show) {
      state.form.reset()
    }
  }
);

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
  }]
}

const transactionStore = useTransactionStore();

const lastSaved = useStorage('lastTransactionSaved', {
  lastSaved: null,
  addAnother: false,
});
const onSubmit = (addAnother = false) => {
  lastSaved.value.addAnother = addAnother;
  const actions = {
    transaction: {
      save: {
        method: "post",
        url: () => route("transactions.store"),
      },
      update: {
        method: "put",
        url: () => route("transactions.update", props.transactionData),
      },
    },
    recurrence: {
      save: {
        method: "post",
        url: () => route("transactions.store-planned"),
      },
      update: {
        method: "update",
        url: () => route("transactions.store-planned"),
      },
    },
  };
  const method = props.transactionData && props.transactionData.id ? "update" : "save";
  const actionType = isRecurrence.value ? "recurrence" : "transaction";
  const action = actions[actionType][method];
  const splitItems = gridSplitsRef.value.getSplits();


  if (!splitItems.every((split: Record<string, string>) => split.amount)) {
    alert("Every split most have an amount");
    return;
  }

  try {
    state.form
      .transform((form) => {
        const data = {
          ...cloneDeep(form),
          resource_type_id: "MANUAL",
          date: format(new Date(form.date), "yyyy-MM-dd"),
          status: "verified",
          direction: form.is_transfer ? TRANSACTION_DIRECTIONS.WITHDRAW : form.direction,
          category_id: form.is_transfer ? null : form.category_id,
          ...state.schedule_settings,
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

          // For multi-currency, we don't use splits in the same way
          data.has_splits = false;
        } else {
          // Standard transaction
          const splitItem = splitItems[0];
          data.category_id = splitItem.category_id;
          data.label_id = splitItem.label_id;
          data.payee_id = splitItem.payee_id;
          data.counter_account_id = form.is_transfer ? splitItem.counter_account_id : null;
          data.account_id = splitItem.account_id;
          data.total = splitItem.amount;
          data.has_splits = false;

          if (splitItems?.length > 1) {
            data.items = splitItems;
            data.has_splits = true;
          }
        }

        lastSaved.value.lastSaved = data;
        return data;
      })
      .submit(action.method, action.url(), {
        preserveScroll: true,
        before: () => {

        },
        onSuccess: () => {
          state.form.reset('description', 'category_id', 'payee_id', 'payee_label', 'total', 'has_splits');
          const newData: ITransaction = lastSaved.value.lastSaved as ITransaction;
          resetSplits(newData);
          nextTick(() => {
            const items = splits.value;
            gridSplitsRef.value?.reset(items);
          })
          if (!lastSaved.value.addAnother) {
            emit("close");
          }


          transactionStore.emitTransaction(newData, action.method, props.transactionData);
          lastSaved.value.addAnother = false;
        },
      });
  } catch (err) {
    console.log(err)
  }
};


const isRecurrence = ref(props.recurrence);
const { form, schedule_settings } = toRefs(state);

const saveText = computed(() => {
  return !props.transactionData?.id ? 'save' : 'update'
})

// Multi-currency handler functions
const handleTransactionCurrencyChange = (currency: string) => {
  transactionCurrency.value = currency;
  currencyAmount.value.currency = currency;

  // Reset manual exchange rate when currency changes
  manualExchangeRate.value = null;
  currentExchangeRate.value = null;
};

const handleAccountChange = (accountId: number) => {
  selectedAccountId.value = accountId;
  state.form.account_id = accountId;

  // Reset exchange rate when account changes
  manualExchangeRate.value = null;
  currentExchangeRate.value = null;
};

const handleManualRateChange = (rate: number) => {
  currentExchangeRate.value = rate;
};

const assignTransactionLabel = (label: Record<string, string>, transaction: Record<string, string>) => {
  axios.post(`/api/transactions/${transaction.id}/labels`, {
    ...label,
    color: generateRandomColor(),
  }).then(() => {
    router.reload()
  });
};
</script>

<template>
  <modal :show="show" :max-width="maxWidth" :full-height="fullHeight" :closeable="closeable" v-slot:default="{ close }"
    @close="$emit('update:show', false)">
    <div class="flex-1 pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header v-if="fullHeight" class="pb-4 text-lg font-bold border-b">
          Create a transaction
        </header>
        <TransactionTypesPicker v-model="form.direction" />

        <div class="mt-2">
          <slot name="content">
            <div>
              {{ form.error }}
              <div class="px-4 md:flex md:space-x-2 md:px-0">
                <AtField label="Date" class="flex justify-between w-full md:w-4/12 md:block">
                  <NDatePicker v-model:value="form.date" type="date" size="large" class="w-48 md:w-full" />
                </AtField>

                <AtField label="Description"
                  class="flex justify-between w-full space-x-2 md:w-8/12 md:block md:space-x-0">
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
                      <CurrencySelector v-model="transactionCurrency" :exclude-currencies="[]"
                        @change="handleTransactionCurrencyChange" />
                    </AtField>

                    <AtField label="Account" v-if="!isTransfer">
                      <NSelect v-model:value="selectedAccountId" :options="multiCurrencyAccountOptions"
                        placeholder="Select multi-currency account" @update:value="handleAccountChange" />
                    </AtField>
                  </div>

                  <!-- Amount Entry -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <AtField label="Amount">
                      <MultiCurrencyInput v-model="currencyAmount" :target-currency="accountPrimaryCurrency"
                        :exchange-rate="currentExchangeRate" :show-exchange-info="showConversionInfo"
                        @currency-change="handleTransactionCurrencyChange" />
                    </AtField>

                    <!-- Exchange Rate Display/Input -->
                    <div v-if="showConversionInfo && needsConversion">
                      <AtField label="Exchange Rate (Optional)">
                        <div class="space-y-2">
                          <LogerInput v-model="manualExchangeRate" type="number" step="0.0001"
                            placeholder="Auto-calculated on payment" @update:model-value="handleManualRateChange" />
                          <div class="text-xs text-gray-500">
                            1 {{ transactionCurrency }} = {{ displayExchangeRate }} {{ accountPrimaryCurrency }}
                          </div>
                        </div>
                      </AtField>
                    </div>
                  </div>

                  <!-- Conversion Preview -->
                  <div v-if="showConversionInfo && needsConversion"
                    class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
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

              <TransactionItems ref="gridSplitsRef" :items="splits" :is-transfer="isTransfer" :mode="form.direction"
                :categories="categoryOptions" :accounts="accountOptions" :full-height="fullHeight" />
            </div>

            <div v-if="isRecurrence">
              <div class="flex space-x-2">
                <AtField label="Repeat this transaction" class="w-full">
                  <NSelect v-model:value="state.schedule_settings.frequency" size="large" :options="[
                    {
                      value: 'WEEKLY',
                      label: 'Weekly',
                    },
                    {
                      value: 'MONTHLY',
                      label: 'Monthly',
                    },
                  ]" />
                </AtField>
                <AtField :label="state.frequencyLabel" class="capitalize">
                  <AtInput type="number" v-model="state.schedule_settings.repeat_on_day_of_month" />
                </AtField>
              </div>
              <div class="flex">
                <AtField label="Ends" class="w-full">
                  <NSelect v-model:value="state.schedule_settings.end_type" :options="[
                    {
                      value: 'NEVER',
                      label: 'Never',
                    },
                    {
                      value: 'DATE',
                      label: 'At',
                    },
                    {
                      value: 'COUNT',
                      label: 'After',
                    },
                  ]" />
                </AtField>
                <AtField label="Date" v-if="state.schedule_settings.end_type == 'DATE'">
                  <NDatePicker v-model:value="state.schedule_settings.end_date" size="lg" />
                </AtField>
                <AtField label="Instances" v-if="state.schedule_settings.end_type == 'COUNT'">
                  <AtInput type="number" v-model="state.schedule_settings.count" />
                </AtField>
              </div>
            </div>
          </slot>
        </div>
      </div>
    </div>

    <footer class="flex items-center justify-between w-full px-6 py-4 space-x-3 bg-base-lvl-2">
      <section class="flex">
        <LogerButtonTab class="hidden rounded bg-base"> Use template</LogerButtonTab>
        <div class="flex space-x-2">
          <AtFieldCheck v-model="isRecurrence" label="Set recurrence" />
          <AtFieldCheck v-if="hasMultiCurrencyAccounts" v-model="isMultiCurrency" label="Multi-currency transaction" />
        </div>
      </section>
      <div class="flex space-x-2">
        <LogerButton @click="close" rounded class="h-10 text-body" :disabled="form.processing">
          Cancel
        </LogerButton>
        <LogerButton class="h-10 text-white capitalize bg-primary" :processing="lastSaved.addAnother && form.processing"
          :disabled="form.processing" @click="onSubmit(true)" rounded>
          {{ saveText }} and another
        </LogerButton>
        <LogerButton class="h-10 text-white capitalize bg-primary"
          :processing="form.processing && !lastSaved.addAnother" :disabled="form.processing" @click="onSubmit()"
          rounded>
          {{ saveText }}
        </LogerButton>
      </div>
    </footer>
  </modal>
</template>
