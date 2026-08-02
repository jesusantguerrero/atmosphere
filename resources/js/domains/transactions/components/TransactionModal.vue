<script setup lang="ts">
import { format, parseISO } from "date-fns";
import { reactive, toRefs, watch, computed, inject, ref, nextTick } from "vue";
import { AtField, AtFieldCheck, AtInput } from "atmosphere-ui";
import { NSelect, NDatePicker } from "naive-ui";
import { useI18n } from "vue-i18n";
import { router, usePage } from "@inertiajs/vue3";

import Modal from "@/Components/atoms/Modal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import TransactionTypesPicker from "./TransactionTypesPicker.vue";
import TransactionItems from "./TransactionItems.vue";

import { TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { cloneDeep } from "lodash";
import { ITransactionLine } from "../models";
import { useTransactionStore } from "@/store/transactions";
import { useInertiaForm, validators } from "@/utils/useInertiaForm";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { useStorage } from "@vueuse/core";
import { formatCurrency, getCurrencyByCode } from '../currency-constants';
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

const props = withDefaults(defineProps<{
  show?: boolean;
  maxWidth?: string;
  closeable?: boolean;
  categories?: any[];
  accounts?: Account[];
  recurrence?: boolean;
  transactionData?: TransactionData;
  mode?: string;
  fullHeight?: boolean;
}>(), {
    closeable: true,
});

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
    currency_code: (window as any)?.logerAppSettings?.currency_code ?? 'USD',
    exchange_rate: null as number | null,
    exchange_amount: null as number | null,
    is_multi_currency: false
  })
});

/**
 * Team-level default currency from app settings. Falls back to USD if not configured.
 * Replaces hardcoded 'USD' literals that broke auto-flip logic for non-USD-primary teams
 * (e.g. a DR user with DOP primary on a multi-currency BHD card was getting auto-flip
 * triggered backwards).
 */
const defaultCurrency = (window as any)?.logerAppSettings?.currency_code ?? 'USD';

state.form.validationSchema({
  description: [validators.isRequired],
});

const splits = ref<Record<string, any>[]>([])

// Autofocus the description field when the modal opens — keyboard-first usability.
const descriptionInputRef = ref<HTMLInputElement | null>(null);

// Lightweight self-contained success toast. Project has no global notification
// provider, so we render a teleported chip directly from this component. Auto-
// clears after 2.5s. Survives the modal close because it lives at the template root.
const successToast = ref<string | null>(null);
let toastTimer: ReturnType<typeof setTimeout> | null = null;
const showSuccessToast = (message: string) => {
  successToast.value = message;
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    successToast.value = null;
    toastTimer = null;
  }, 2500);
};

// Multi-currency state
const isMultiCurrency = ref(false);
const transactionCurrency = ref(defaultCurrency);
const selectedAccountId = ref<number | null>(null);
const manualExchangeRate = ref<number | null>(null);
const currentExchangeRate = ref<number | null>(null);

const currencyAmount = ref({
  amount: 0,
  currency: defaultCurrency
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
const accountOptions = inject<{ value: number; label: string }[]>("accountsOptions", []);

// Read the live first-split state from the TransactionItems child via its exposed
// reactive `splits`. Used for the transfer preview readback.
const firstSplit = computed<Record<string, any> | null>(() => {
  const grid: any = gridSplitsRef.value;
  return grid?.getSplits?.()?.[0] ?? null;
});

// Date presets — most transactions are entered for today or yesterday.
// `form.date` is stored as a Date instance; comparison is by yyyy-mm-dd to avoid
// time-of-day false negatives.
const dateAsIso = (date: Date | null | undefined) => {
  if (!date) return '';
  const d = date instanceof Date ? date : new Date(date as any);
  return isNaN(d.getTime()) ? '' : format(d, 'yyyy-MM-dd');
};

const setDatePreset = (preset: 'today' | 'yesterday') => {
  const d = new Date();
  if (preset === 'yesterday') d.setDate(d.getDate() - 1);
  d.setHours(0, 0, 0, 0);
  state.form.date = d;
};

const isDatePreset = (preset: 'today' | 'yesterday') => {
  const target = new Date();
  if (preset === 'yesterday') target.setDate(target.getDate() - 1);
  return dateAsIso(state.form.date as any) === dateAsIso(target);
};

const transferPreview = computed(() => {
  if (!isTransfer.value) return null;
  const split = firstSplit.value;
  if (!split?.account_id || !split?.counter_account_id) return null;
  const amount = Number(split.amount ?? 0);
  if (!amount) return null;
  const source = accountOptions.find((a) => a.value === split.account_id);
  const destination = accountOptions.find((a) => a.value === split.counter_account_id);
  if (!source || !destination) return null;
  return { source: source.label, destination: destination.label, amount };
});

// Multi-currency computed properties
const multiCurrencyAccounts = computed(() => {
  return (props.accounts || []).filter((account: Account) => account.is_multi_currency);
});

const hasMultiCurrencyAccounts = computed(() => {
  return multiCurrencyAccounts.value.length > 0;
});

// The account chosen in the grid's first row (mirrored up via @first-row-change)
// and the amount typed there. These drive the conversion strip.
const gridAccountId = ref<number | null>(null);
const gridAmount = ref(0);

const onFirstRowChange = (payload: { account_id: number | null; amount: number }) => {
  gridAccountId.value = payload?.account_id ?? null;
  gridAmount.value = Number(payload?.amount ?? 0);
};

// The native currency of the account picked in the grid (not a selector — it
// comes from the account). Falls back to the team default when none is chosen.
const activeAccountCurrency = computed(() => {
  const acc = (props.accounts || []).find((account: Account) => account.id === gridAccountId.value);
  return acc?.currency_code || defaultCurrency;
});

// Symbol (e.g. RD$) of the account's native currency, for the conversion estimate.
const activeAccountSymbol = computed(() => {
  return getCurrencyByCode(activeAccountCurrency.value)?.symbol || activeAccountCurrency.value;
});

// Converted amount formatted with thousands + 2 decimals (e.g. "5,900.00").
const convertedDisplay = computed(() => {
  const n = Number(convertedAmount.value) || 0;
  return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
});

// Conversion only matters once an account is picked AND the transaction currency
// differs from that account's own currency.
const needsConversion = computed(() => {
  return !!gridAccountId.value && transactionCurrency.value !== activeAccountCurrency.value;
});

const showConversion = computed(() => {
  return hasMultiCurrencyAccounts.value && needsConversion.value;
});

const convertedAmount = computed(() => {
  if (currentExchangeRate.value && gridAmount.value) {
    return convertAmount(gridAmount.value, currentExchangeRate.value);
  }
  return 0;
});

// Reset any stale exchange rate when the transaction currency changes.
watch(transactionCurrency, () => {
  manualExchangeRate.value = null;
  currentExchangeRate.value = null;
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

    // Set multi-currency state if transaction has multi-currency data, OR if
    // it's just a tx that happens to be in a non-team-default currency (e.g.
    // a $1 USD charge on a multi-currency DOP card). Without the second branch
    // the modal would auto-fall back to the team default on save, silently
    // flipping the tx's currency_code and corrupting the underlying record.
    const txCurrency = newValue.currency_code;
    const isInForeignCurrency = !!txCurrency && txCurrency !== defaultCurrency;

    if (newValue.is_multi_currency || isInForeignCurrency) {
      isMultiCurrency.value = true;
      transactionCurrency.value = txCurrency || defaultCurrency;
      selectedAccountId.value = newValue.account_id || null;
      currencyAmount.value = {
        amount: newValue.total || 0,
        currency: txCurrency || defaultCurrency,
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
      transactionCurrency.value = defaultCurrency;
      selectedAccountId.value = null;
      manualExchangeRate.value = null;
      currentExchangeRate.value = null;
      currencyAmount.value = { amount: 0, currency: defaultCurrency };
    }
    if (show) {
      // Autofocus the description after the modal transition settles.
      nextTick(() => {
        const el: any = descriptionInputRef.value;
        const input = el?.$el?.querySelector?.('input') ?? el?.querySelector?.('input') ?? el;
        input?.focus?.();
      });
    } else {
      state.form.reset();
      // Reset multi-currency state
      isMultiCurrency.value = false;
      transactionCurrency.value = defaultCurrency;
      selectedAccountId.value = null;
      manualExchangeRate.value = null;
      currentExchangeRate.value = null;
      currencyAmount.value = { amount: 0, currency: defaultCurrency };
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
// Client-side validation surface. Replaces the legacy `alert("...")` on amount
// and adds checks the backend would otherwise reject silently.
const { t } = useI18n();
const validationError = ref<string | null>(null);

const validateBeforeSubmit = (splitItems: Record<string, any>[]): boolean => {
  validationError.value = null;
  for (const split of splitItems) {
    if (!split.amount || Number(split.amount) <= 0) {
      validationError.value = 'Every split must have an amount greater than 0.';
      return false;
    }
    if (state.form.is_transfer) {
      if (!split.account_id || !split.counter_account_id) {
        validationError.value = 'Transfers need both source and destination accounts.';
        return false;
      }
      if (Number(split.account_id) === Number(split.counter_account_id)) {
        validationError.value = 'Source and destination cannot be the same account.';
        return false;
      }
    } else {
      // Non-transfer (income + expense): a payee is always required so the
      // "gasto por categoria" widget and payee reports are never left empty.
      if (!split.payee_id) {
        validationError.value = t('Every income and expense needs a payee.');
        return false;
      }
      // Category is required for expenses (WITHDRAW). Income (DEPOSIT) is allowed
      // to leave the category empty here because the submit .transform step
      // defaults it to "Ready to Assign" (readyToAssignId) right after this runs.
      if (state.form.direction === TRANSACTION_DIRECTIONS.WITHDRAW && !split.category_id) {
        validationError.value = t('Every income and expense needs a category.');
        return false;
      }
    }
  }
  return true;
};

// Fix (Hope): the "Ready to Assign" (inflow) category, resolved by its stable
// display_id. Income only counts in "Income"/"Por asignar" when it lands in the
// inflow group (see TransactionService::getIncome → byCategories(['inflow'])),
// so a fresh salary with no category picked shows 0 until categorized by hand.
const page = usePage();
const readyToAssignId = computed<number | null>(() => {
  const find = (list: any[]): number | null => {
    for (const c of (list || [])) {
      if (c?.display_id === 'ready_to_assign') return c.id;
      const sub = find(c?.sub_categories || c?.subCategories);
      if (sub) return sub;
    }
    return null;
  };
  return find((page.props as any).categories || []);
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

  if (!validateBeforeSubmit(splitItems)) return;

  try {
    state.form
      .transform((form) => {
        const data = {
          ...cloneDeep(form),
          resource_type_id: "MANUAL",
          date: format(new Date(form.date), "yyyy-MM-dd"),
          status: isRecurrence.value ? "planned" : "verified",
          direction: form.is_transfer ? TRANSACTION_DIRECTIONS.WITHDRAW : form.direction,
          category_id: form.is_transfer ? null : form.category_id,
          ...(isRecurrence.value ? {
            ...state.schedule_settings,
            start_date: format(new Date(form.date), "yyyy-MM-dd"),
          } : {}),
        };

        // Single unified entry: the grid holds account + amount + category.
        const splitItem = splitItems[0];
        (data as any).category_id = splitItem.category_id;
        (data as any).label_id = splitItem.label_id;
        (data as any).payee_id = splitItem.payee_id;
        (data as any).counter_account_id = form.is_transfer ? splitItem.counter_account_id : null;
        (data as any).account_id = splitItem.account_id;
        (data as any).total = splitItem.amount;
        (data as any).currency_code = transactionCurrency.value;
        (data as any).has_splits = false;

        if (splitItems?.length > 1) {
          (data as any).items = splitItems;
          (data as any).has_splits = true;
        }

        // Multi-currency: the charge currency differs from the account's own
        // currency. Flag it and attach the (optional) manual rate + converted
        // amount so the ledger reconciles once the payment settles.
        if (needsConversion.value) {
          (data as any).is_multi_currency = true;
          if (manualExchangeRate.value) {
            (data as any).exchange_rate = manualExchangeRate.value;
            (data as any).exchange_amount = convertAmount(Number(splitItem.amount) || 0, manualExchangeRate.value);
          }
        }

        // An income with no category picked won't count anywhere (getIncome
        // filters by the inflow group). Default it to Ready to Assign so the
        // salary counts on its own. Placed AFTER the split block, which sets
        // category_id from the (possibly empty) category field. Safe no-op if
        // the category can't be resolved.
        if (!(data as any).is_transfer
            && (data as any).direction === TRANSACTION_DIRECTIONS.DEPOSIT
            && !(data as any).category_id
            && readyToAssignId.value) {
          (data as any).category_id = readyToAssignId.value;
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

          // Confirm the save so the user knows it landed — modal closes silently otherwise.
          const verb = props.transactionData?.id ? 'updated' : 'saved';
          showSuccessToast(`Transaction ${verb}`);

          // Refresh page props so lists/totals update without a manual reload.
          router.reload({ preserveScroll: true });

          if (!lastSaved.value.addAnother) {
            emit("close");
          }

          transactionStore.emitTransaction(newData, action.method, props.transactionData);
          lastSaved.value.addAnother = false;
        },
      });
  } catch (err) {
  }
};


const isRecurrence = ref(props.recurrence);
const { form, schedule_settings } = toRefs(state);

const saveText = computed(() => {
  return !props.transactionData?.id ? 'save' : 'update'
})

const handleManualRateChange = (rate: number | string | null) => {
  const n = rate === '' || rate === null || rate === undefined ? null : Number(rate);
  manualExchangeRate.value = n as number | null;
  currentExchangeRate.value = Number.isFinite(n as number) ? (n as number) : null;
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
    <div class="flex-1 min-h-0 pb-4 overflow-y-auto transaction-modal-body bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header v-if="fullHeight" class="sticky top-0 z-10 flex items-center justify-between px-4 py-3 text-base font-bold border-b bg-base-lvl-3 sm:static sm:px-0 sm:pb-3 sm:pt-0 sm:text-lg">
          <span>{{ $t('Create a transaction') }}</span>
          <button
            type="button"
            class="p-1 -mr-1 rounded text-body-1/60 hover:text-body transition"
            :aria-label="$t('Close')"
            @click="close"
          >
            <i class="fa fa-times text-base" />
          </button>
        </header>
        <header v-else class="flex justify-end -mt-1 -mr-2">
          <button
            type="button"
            class="p-1 rounded text-body-1/60 hover:text-body transition"
            :aria-label="$t('Close')"
            @click="close"
          >
            <i class="fa fa-times text-base" />
          </button>
        </header>
        <div class="px-4 mt-3 sm:px-0">
          <TransactionTypesPicker v-model="form.direction" />
        </div>

        <div class="mt-3">
          <slot name="content">
            <div>
              {{ form.error }}
              <div class="px-4 md:flex md:items-start md:space-x-2 md:px-0">
                <AtField label="Date" class="w-full md:w-3/12">
                  <!-- Quick presets live in the label's action slot (right of the
                       "Date" label) so they don't add a row under the input. -->
                  <template #action>
                    <div class="flex gap-1.5">
                      <button
                        type="button"
                        class="px-2 py-0.5 text-[11px] font-medium rounded transition"
                        :class="isDatePreset('today') ? 'bg-primary text-white' : 'bg-base-lvl-2 text-body-1 hover:bg-base-lvl-1'"
                        @click="setDatePreset('today')"
                      >
                        {{ $t('Today') }}
                      </button>
                      <button
                        type="button"
                        class="px-2 py-0.5 text-[11px] font-medium rounded transition"
                        :class="isDatePreset('yesterday') ? 'bg-primary text-white' : 'bg-base-lvl-2 text-body-1 hover:bg-base-lvl-1'"
                        @click="setDatePreset('yesterday')"
                      >
                        {{ $t('Yesterday') }}
                      </button>
                    </div>
                  </template>
                  <NDatePicker v-model:value="form.date" type="date" size="large" class="w-full" />
                </AtField>

                <AtField label="Description" class="w-full md:w-9/12">
                  <LogerInput
                    ref="descriptionInputRef"
                    v-model="form.description"
                    class="w-full"
                  />
                </AtField>
              </div>

              <TransactionItems ref="gridSplitsRef" :items="splits" :is-transfer="isTransfer" :mode="form.direction"
                :categories="categoryOptions" :accounts="accountOptions" :full-height="fullHeight"
                v-model:currency-code="transactionCurrency"
                :show-currency-picker="hasMultiCurrencyAccounts"
                @first-row-change="onFirstRowChange" />

              <!-- Multi-currency conversion strip. Appears ONLY when the picked
                   transaction currency differs from the selected account's own
                   currency — no modes, no blue block, no duplicate selectors. -->
              <div v-if="showConversion" class="mx-4 md:mx-0 mt-3">
                <div class="rounded-lg border border-amber-300/40 bg-amber-400/[0.07] px-3 py-2">
                  <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5">
                    <div class="flex items-center gap-2 text-sm text-body">
                      <span class="font-semibold whitespace-nowrap">1 {{ transactionCurrency }} =</span>
                      <input
                        v-model="manualExchangeRate"
                        type="text"
                        inputmode="decimal"
                        class="w-20 px-2 py-1 text-sm text-center rounded-md bg-base-lvl-1 border border-base focus:border-primary focus:outline-none"
                        :placeholder="$t('rate')"
                        @input="handleManualRateChange(($event.target as HTMLInputElement).value)"
                      />
                      <span class="font-semibold whitespace-nowrap">{{ activeAccountCurrency }}</span>
                    </div>
                    <div v-if="convertedAmount" class="text-sm font-bold text-amber-500 whitespace-nowrap">
                      ≈ {{ activeAccountSymbol }} {{ convertedDisplay }}
                    </div>
                  </div>
                  <div class="mt-1 flex items-center gap-1.5 text-xs text-body-1/60">
                    <i class="fa fa-rotate" />
                    <span>{{ $t('Editable rate — settles when the payment clears.') }}</span>
                  </div>
                </div>
              </div>
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
                  <NDatePicker v-model:value="state.schedule_settings.end_date" type="date" size="large" class="w-full" />
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

    <!-- Transfer readback — confirms the source → destination + amount before submit. -->
    <section
      v-if="transferPreview"
      class="flex items-center gap-2 px-6 py-2 text-sm bg-secondary/10 border-t border-secondary/20 text-secondary"
    >
      <i class="fa fa-arrow-right" />
      <span class="font-medium">{{ formatCurrency(transferPreview.amount) }}</span>
      <span class="text-body-1/70">{{ $t('from') }}</span>
      <span class="font-medium">{{ transferPreview.source }}</span>
      <span class="text-body-1/70">{{ $t('to') }}</span>
      <span class="font-medium">{{ transferPreview.destination }}</span>
    </section>

    <!-- Options live above the footer now (was buried in footer-left as small toggles).
         Recurrence and multi-currency are major mode changes — they deserve to live in
         the form area, not next to Cancel. -->
    <section class="flex flex-wrap items-center gap-x-6 gap-y-2 px-6 py-2 text-sm border-t border-base bg-base-lvl-3">
      <AtFieldCheck v-model="isRecurrence" :label="$t('Set recurrence')" />
    </section>

    <!-- Inline validation surface — appears only when validateBeforeSubmit fails. -->
    <div
      v-if="validationError"
      class="px-6 py-2 text-sm text-error bg-error/10 border-t border-error/30"
      role="alert"
    >
      {{ validationError }}
    </div>

    <footer
      class="flex flex-col-reverse w-full gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-end sm:px-6 sm:py-4 bg-base-lvl-2"
      :style="{ paddingBottom: fullHeight ? 'calc(0.75rem + env(safe-area-inset-bottom))' : undefined }"
    >
      <LogerButton
        variant="neutral"
        rounded
        class="h-11 w-full sm:h-10 sm:w-auto"
        :disabled="form.processing"
        @click="close"
      >
        {{ $t('Cancel') }}
      </LogerButton>
      <LogerButton
        variant="inverse"
        rounded
        class="h-11 w-full sm:h-10 sm:w-auto"
        :processing="lastSaved.addAnother && form.processing"
        :disabled="form.processing"
        @click="onSubmit(true)"
      >
        <span class="sm:hidden">{{ saveText === 'save' ? $t('Save & add another') : $t('Update & add another') }}</span>
        <span class="hidden sm:inline">{{ saveText === 'save' ? $t('Save and add another') : $t('Update and add another') }}</span>
      </LogerButton>
      <LogerButton
        variant="primary"
        rounded
        class="h-11 w-full sm:h-10 sm:w-auto"
        :processing="form.processing && !lastSaved.addAnother"
        :disabled="form.processing"
        @click="onSubmit()"
      >
        {{ saveText === 'save' ? $t('Save') : $t('Update') }}
      </LogerButton>
    </footer>
  </modal>

  <!-- Self-contained success toast. Lives at template root (outside the modal teleport)
       so it persists after the modal closes. Auto-clears after 2.5s via showSuccessToast. -->
  <Teleport to="body">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="successToast"
        class="fixed bottom-6 right-6 z-[2000] flex items-center gap-2 px-4 py-2.5 rounded-md shadow-lg bg-success text-white text-sm font-medium"
        role="status"
      >
        <i class="fa fa-check-circle" />
        <span>{{ successToast }}</span>
      </div>
    </transition>
  </Teleport>
</template>

<style>
/* atmosphere-ui ships `.form-group { margin: 15px 0 }` and `.form-group label
   { margin: .5rem 0 }` globally. That spacing is too generous inside this
   modal — fields end up with ~30px between them on mobile. Tighten it here
   without affecting other forms in the app. */
.transaction-modal-body .form-group {
    margin-top: 0.5rem;
    margin-bottom: 0.5rem;
}
.transaction-modal-body .form-group > header label {
    margin: 0 0 0.25rem;
}
/* NDatePicker (naive-ui .n-input) rendered two-tone: the inner <input> got the
   app's `.dark .form-group input` base-lvl-2 override while the naive-ui wrapper
   (incl. the calendar-icon suffix) kept its own theme color. Unify both ends. */
.transaction-modal-body .n-input {
    background-color: rgb(var(--c-base-lvl-2)) !important;
}
.transaction-modal-body .n-input .n-input__input-el,
.transaction-modal-body .n-input .n-input__suffix,
.transaction-modal-body .n-input .n-input__prefix {
    background-color: transparent !important;
}
</style>
