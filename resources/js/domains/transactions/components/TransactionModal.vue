<script setup lang="ts">
import { format, parseISO } from "date-fns";
import { reactive, toRefs, watch, computed, inject, ref, nextTick } from "vue";
import { AtField, AtFieldCheck, AtInput } from "atmosphere-ui";
import { NSelect, NDatePicker } from "naive-ui";
import { router } from "@inertiajs/vue3";

import Modal from "@/Components/atoms/Modal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import TransactionTypesPicker from "./TransactionTypesPicker.vue";
import TransactionItems from "./TransactionItems.vue";
import CurrencySelector from "./CurrencySelector.vue";
import MultiCurrencyInput from "./MultiCurrencyInput.vue";

import { TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { cloneDeep } from "lodash";
import { ITransactionLine } from "../models";
import { useTransactionStore } from "@/store/transactions";
import { useInertiaForm, validators } from "@/utils/useInertiaForm";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { useStorage } from "@vueuse/core";
import { formatCurrency } from '../currency-constants';
import { convertAmount } from '../multi-currency-utils';
import { generateRandomColor } from "@/utils";
import axios from "axios";

interface Account {
  id: number;
  name: string;
  currency_code: string;
  is_multi_currency?: boolean;
  secondary_currencies?: string[];
}

interface TransactionData {
  id?: number;
  name?: string;
  payee_id?: string;
  payee_label?: string;
  label_id?: string;
  label_name?: string;
  date?: string;
  is_transfer?: boolean;
  description?: string;
  direction?: string;
  category_id?: number;
  counter_account_id?: number;
  account_id?: number;
  total?: number;
  has_splits?: boolean;
  currency_code?: string;
  exchange_rate?: number;
  exchange_amount?: number;
  is_multi_currency?: boolean;
  splits?: any[];
  lines?: ITransactionLine[];
  payee?: { id: string; name: string };
  category?: { id: number };
  account?: { id: number };
  counter_account?: { id: number };
}

const props = defineProps<{
  show?: boolean;
  maxWidth?: string;
  closeable?: boolean;
  categories?: any[];
  accounts?: Account[];
  recurrence?: boolean;
  transactionData?: TransactionData;
  mode?: string;
  fullHeight?: boolean;
}>();

const emit = defineEmits(["close", "saved", "update:show"]);

const state = reactive({
  frequencyLabel: "every",
  schedule_settings: {
    end_date: null as Date | null,
    count: null as number | null,
    end_type: "NEVER" as string,
    final_item_date: null as Date | null,
    interval: 1,
    frequency: "monthly" as string,
    repeat_at_end_of_month: false,
    repeat_on_day_of_month: null as number | null,
    start_date: null as Date | null,
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
    category_id: null as number | null,
    counter_account_id: null as number | null,
    account_id: null as number | null,
    total: 0,
    has_splits: false,
    // Multi-currency fields
    currency_code: 'USD',
    exchange_rate: null as number | null,
    exchange_amount: null as number | null,
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
const selectedAccountId = ref<number | null>(null);
const manualExchangeRate = ref<number | null>(null);
const currentExchangeRate = ref<number | null>(null);

const currencyAmount = ref({
  amount: 0,
  currency: 'USD'
});

watch(
  () => state.form.direction,
  (direction) => {
    if (typeof direction === 'string' && direction.toLowerCase() === "transfer") {
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
  return (props.accounts || []).filter((account: Account) => account.is_multi_currency);
});

const hasMultiCurrencyAccounts = computed(() => {
  return multiCurrencyAccounts.value.length > 0;
});

const shouldShowMultiCurrency = computed(() => {
  return isMultiCurrency.value || hasMultiCurrencyAccounts.value;
});

const multiCurrencyAccountOptions = computed(() => {
  return multiCurrencyAccounts.value.map((account: Account) => ({
    value: account.id,
    label: `${account.name} (${account.currency_code})`,
    currency_code: account.currency_code,
    secondary_currencies: account.secondary_currencies || []
  }));
});

const selectedAccount = computed(() => {
  return multiCurrencyAccounts.value.find((account: Account) => account.id === selectedAccountId.value);
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
    if (!props.transactionData) return;

    const newValue = { ...props.transactionData };
    Object.keys(state.form.data()).forEach((field) => {
      const fieldData = newValue[field as keyof TransactionData];
      if (field == "date" && newValue[field]) {
        state.form[field] = parseISO(fieldData as string);
      } else if (field == 'is_transfer') {
        state.form[field] = Boolean(fieldData);
      } else {
        state.form[field] = fieldData || state.form[field];
      }
    });

    // Set multi-currency state if transaction has multi-currency data
    if (newValue.is_multi_currency) {
      isMultiCurrency.value = true;
      transactionCurrency.value = newValue.currency_code || 'USD';
      selectedAccountId.value = newValue.account_id || null;
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
      state.form.direction = TRANSACTION_DIRECTIONS.TRANSFER;
    }

    if (newValue.has_splits) {
      splits.value = props.transactionData?.splits ?? props.transactionData.lines?.filter((line: ITransactionLine) => line.is_split) ?? [];
    } else {
      const anchorLine = newValue.lines?.find((item: any) => item.anchor);
      splits.value = [{
        payee_id: newValue.payee_id ?? newValue.payee?.id ?? '',
        payee_label: newValue.payee?.name ?? '',
        label_id: newValue.label_id ?? anchorLine?.labels?.at?.(0)?.id ?? '',
        label_name: anchorLine?.labels?.at?.(0)?.name ?? '',
        category_id: newValue.category_id ?? newValue.category?.id ?? '',
        category: newValue.category ?? '',
        counter_account_id: newValue.counter_account_id ?? newValue.counter_account?.id ?? '',
        account_id: newValue.account_id ?? newValue.account?.id ?? '',
        account: newValue.account ?? '',
        amount: newValue.total ?? 0,
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
      // Reset multi-currency state for new transactions
      isMultiCurrency.value = false;
      transactionCurrency.value = 'USD';
      selectedAccountId.value = null;
      manualExchangeRate.value = null;
      currentExchangeRate.value = null;
      currencyAmount.value = { amount: 0, currency: 'USD' };
    } else if (!show) {
      state.form.reset();
      // Reset multi-currency state
      isMultiCurrency.value = false;
      transactionCurrency.value = 'USD';
      selectedAccountId.value = null;
      manualExchangeRate.value = null;
      currentExchangeRate.value = null;
      currencyAmount.value = { amount: 0, currency: 'USD' };
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
          (data as any).currency_code = transactionCurrency.value;
          (data as any).total = currencyAmount.value.amount;
          (data as any).is_multi_currency = true;
          (data as any).account_id = selectedAccountId.value;

          // Only set exchange rate if manually provided
          if (manualExchangeRate.value) {
            (data as any).exchange_rate = manualExchangeRate.value;
            (data as any).exchange_amount = convertAmount(currencyAmount.value.amount, manualExchangeRate.value);
          }

          // For multi-currency, we don't use splits in the same way
          (data as any).has_splits = false;
        } else {
          // Standard transaction
          const splitItem = splitItems[0];
          (data as any).category_id = splitItem.category_id;
          (data as any).label_id = splitItem.label_id;
          (data as any).payee_id = splitItem.payee_id;
          (data as any).counter_account_id = form.is_transfer ? splitItem.counter_account_id : null;
          (data as any).account_id = splitItem.account_id;
          (data as any).total = splitItem.amount;
          (data as any).has_splits = false;

          if (splitItems?.length > 1) {
            (data as any).items = splitItems;
            (data as any).has_splits = true;
          }
        }

        lastSaved.value.lastSaved = data as any;
        return data;
      })
      .submit(action.method, action.url(), {
        preserveScroll: true,
        before: () => {

        },
        onSuccess: () => {
          state.form.reset('description', 'category_id', 'payee_id', 'payee_label', 'total', 'has_splits');
          const newData: any = lastSaved.value.lastSaved;
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
const handleTransactionCurrencyChange = (currency: { code: string; name: string; symbol: string } | null) => {
  if (currency) {
    transactionCurrency.value = currency.code;
    currencyAmount.value.currency = currency.code;

    // Auto-enable multicurrency mode if currency is different from USD
    if (currency.code !== 'USD' && hasMultiCurrencyAccounts.value) {
      isMultiCurrency.value = true;
    }

    // Reset manual exchange rate when currency changes
    manualExchangeRate.value = null;
    currentExchangeRate.value = null;
  }
};

const handleAccountChange = (accountId: number) => {
  selectedAccountId.value = accountId;
  state.form.account_id = accountId;

  // Reset exchange rate when account changes
  manualExchangeRate.value = null;
  currentExchangeRate.value = null;
};

const handleManualRateChange = (rate: number | null) => {
  manualExchangeRate.value = rate;
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
                <AtField label="Date" class="flex justify-between w-full md:w-3/12 md:block">
                  <NDatePicker v-model:value="form.date" type="date" size="large" class="w-48 md:w-full" />
                </AtField>

                <AtField label="Description"
                  class="flex justify-between w-full space-x-2 md:w-6/12 md:block md:space-x-0">
                  <LogerInput v-model="form.description" class="w-48 md:w-full" />
                </AtField>

                <AtField label="Currency" class="flex justify-between w-full md:w-3/12 md:block"
                  v-if="hasMultiCurrencyAccounts">
                  <CurrencySelector v-model="transactionCurrency" :exclude-currencies="[]"
                    @change="handleTransactionCurrencyChange" />
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
                        @currency-change="(currency: string) => { transactionCurrency.value = currency; currencyAmount.value.currency = currency; }" />
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
          <AtFieldCheck v-if="hasMultiCurrencyAccounts" v-model="isMultiCurrency" label="Multi-currency" />
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
