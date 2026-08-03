<script setup lang="ts">
import { reactive, computed, inject, ref, watch, nextTick } from "vue";
import { AtField } from "atmosphere-ui";
import { NSelect } from "naive-ui";

import InputMoney from "@/Components/atoms/InputMoney.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
import CategoryPicker from "./CategoryPicker.vue";
import CurrencySelector from "./CurrencySelector.vue";
import { IAccount, ICategory } from "../models";
import { getCurrencyByCode } from "../currency-constants";
import { formatMoney } from "@/utils";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import { TRANSACTION_DIRECTIONS } from "..";
import axios from "axios";

interface SplitItem {
    payee_id: null|number,
    payee_label: string,
    label_id: null|number,
    label_name: string,
    date: null|Date,
    description: string,
    category_id: null|number,
    counter_account_id: null|number,
    account_id: null|number,
    amount: string,
    history: string|number[];
    concept: string;
}

const props = defineProps<{
  items: SplitItem[],
  categories: ICategory[],
  accounts: IAccount[],
  isTransfer: boolean
  fullHeight: boolean;
  mode?: string;
  currencyCode?: string;
  showCurrencyPicker?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:currencyCode', code: string): void;
  (e: 'firstRowChange', payload: { account_id: number | null; amount: number }): void;
}>();

// Currency symbol for the amount inputs. Derived from the transaction's
// selected currency (falls back to the team default) so it always agrees
// with the Currency dropdown instead of a hardcoded peso symbol.
const currencySymbol = computed(() => {
  const code = props.currencyCode
    || (window as any)?.logerAppSettings?.currency_code
    || "USD";
  return getCurrencyByCode(code)?.symbol ?? code;
});

// The account chosen on the first row, resolved to its full record so we can
// surface its native currency (the chip next to the account, and the modal's
// conversion hint when the picked currency differs from it).
const firstRowAccountCurrency = computed(() => {
  const acc = (props.accounts || []).find((a) => a.id === splits[0]?.account_id);
  return acc?.currency_code || null;
});

const onCurrencyPicked = (currency: { code: string } | null) => {
  if (currency?.code) emit('update:currencyCode', currency.code);
};

// After selecting from the filterable account NSelect, naive-ui keeps its search
// input focused; the next click (e.g. on Category) is consumed blurring it, so the
// user needs an extra click. Blur here so the Category dropdown opens on first click.
const onAccountPicked = () => {
  // Let naive-ui finish closing its menu first, THEN drop the focus that the
  // filterable input keeps (otherwise the next click on Category is consumed
  // blurring it, forcing an extra click). Blurring too early (nextTick) fought
  // the menu close, so defer past it.
  setTimeout(() => {
    const el = document.activeElement as HTMLElement | null;
    if (el && el.tagName === 'INPUT') el.blur();
  }, 60);
};
const accountLabel = computed(() => {
  return !props.isTransfer ? "Account" : "Source account";
});
const categoryLabel = computed(() => {
  return !props.isTransfer ? "Category" : "Destination account";
});

const categoryField = computed(() => {
  return props.isTransfer ? "counter_account_id" : "category_id";
});

const categoryOptions = inject<{ display_id?: string }[]>("categoryOptions", []);
const accountsOptions = inject<{ id: number; label: string }[]>("accountsOptions", []);

const categoryAccounts = computed(() => {
    if (props.isTransfer) {
        return accountsOptions;
    } else {
        return categoryOptions.filter((category: { display_id?: string }) => {
            const isInflow = props.mode == TRANSACTION_DIRECTIONS.DEPOSIT;
            return (isInflow && category.display_id == "inflow") || !isInflow;
        });
    }
});

const splits = reactive<SplitItem[]>(props.items ?? []);
const hasSplits = computed(() => splits.length > 1);

const defaultRow: SplitItem = {
    payee_id: null,
    payee_label: "",
    label_id: null,
    label_name: "",
    date: null,
    description: "",
    category_id: null,
    counter_account_id: null,
    account_id: null,
    amount: "0",
    history: [],
    concept: ""
};

const splitsTotal = computed(() =>
    splits.reduce((total: number, splitItem): number => {
        return total + parseFloat(splitItem.amount ?? "0");
    }, 0)
)

const addSplit = () => {
  splits.push({ ...defaultRow });
};

const removeSplit = (index: number) => {
  splits.splice(index, 1);
};

if (!props.items?.length) {
  addSplit();
}

const labels = ref([]);

defineExpose({
  getSplits() {
    return splits;
  },
  // Accepts the rows the parent wants to keep after a save (e.g. 'Save and add
  // another' preserves the account so you don't re-pick it every time). Falls
  // back to a single blank row when nothing is passed. Previously ignored its
  // argument and always reset to defaultRow, wiping the account to 'Please Select'.
  reset(items?: SplitItem[]) {
    const next = (items && items.length)
      ? items.map((item) => ({ ...defaultRow, ...item }))
      : [{ ...defaultRow }];
    splits.splice(0, splits.length, ...next);
  },
});

const isPickerOpen = ref(false);

// Auto-fetch any planned transaction matching the picked category and surface it
// inline as a passive notice. Replaces the old "Show Planned Transaction Options"
// click-to-reveal toggle — the info should be visible the moment a category is set.
const relatedPlanned = ref<{ [key: number]: any }>({});

async function fetchRelatedPlanned(index: number, categoryId: number | null) {
  if (!categoryId) {
    relatedPlanned.value[index] = null;
    return;
  }
  try {
    const res = await axios.get(`/api/planned?category_id=${categoryId}`);
    relatedPlanned.value[index] = res.data || null;
  } catch (e) {
    relatedPlanned.value[index] = null;
  }
}

async function markPlannedAsCompleted(index: number) {
  const planned = relatedPlanned.value[index];
  if (!planned) return;
  await axios.post(`/api/planned/${planned.id}/complete`);
  await fetchRelatedPlanned(index, splits[index].category_id);
}

// Re-fetch the planned info when the category changes on any split. Skip for transfers
// (no category concept) so we don't fire useless requests.
watch(
  () => splits.map((s) => s.category_id),
  (categoryIds) => {
    if (props.isTransfer) return;
    categoryIds.forEach((catId, idx) => fetchRelatedPlanned(idx, catId));
  },
  { deep: true }
);

// Bubble the first row's account + amount up so the modal can show the
// multi-currency conversion strip only when the picked currency differs from
// the account's native currency. immediate so the modal syncs on open.
watch(
  () => ({ account_id: splits[0]?.account_id ?? null, amount: Number(splits[0]?.amount ?? 0) }),
  (payload) => emit('firstRowChange', payload),
  { immediate: true, deep: true }
);
</script>


<template>
  <section>
    <section
      v-for="(split, index) in splits"
      :key="index"
      class="px-4 md:px-0 rounded-md even:bg-base-lvl-2"
    >


      <section v-if="!index">
          <div class="md:flex md:items-start md:gap-3">
            <div class="relative w-full md:flex-1">
              <span
                v-if="showCurrencyPicker && firstRowAccountCurrency"
                class="absolute right-0 top-0 z-10 px-2 py-0.5 text-[10px] font-bold tracking-wide rounded-full bg-info/10 text-info border border-info/25"
              >{{ firstRowAccountCurrency }}</span>
              <AtField
                :label="$t(accountLabel)"
                class="w-full md:my-0 md:-mt-4"
              >
                <NSelect
                  filterable
                  clearable
                  tag
                  size="large"
                  class="w-full"
                  v-model:value="split.account_id"
                  :default-expand-all="true"
                  :options="accountsOptions"
                  @update:value="onAccountPicked"
                />
              </AtField>
            </div>
            <AtField
              v-if="showCurrencyPicker"
              :label="$t('Currency')"
              class="w-full md:w-44 md:flex-shrink-0 md:my-0 md:-mt-4"
            >
              <CurrencySelector :model-value="currencyCode" :clearable="false" @change="onCurrencyPicked" />
            </AtField>
          </div>

        <div class="px-2 py-1 text-center" v-if="hasSplits">
            {{  formatMoney(splitsTotal) }}
        </div>
      </section>

      <header class="flex justify-between pt-2 -mb-4" v-if="hasSplits">
        <h4 class="font-bold">{{ $t('Split') }} ({{ index + 1 }}/{{ splits.length }})</h4>
        <button @click="removeSplit(index)">
          <IMdiTrash />
        </button>
      </header>

      <div class="md:flex md:space-x-3 md:px-0 md:-mt-4">
        <AtField
          :label="$t('Payee')"
          class="w-full md:w-4/12"
          v-if="!isTransfer"
        >
          <!-- Required marker: payee is mandatory on income + expense. -->
          <template #action>
            <span class="font-bold text-error" aria-hidden="true">*</span>
          </template>
          <LogerApiSimpleSelect
            v-model="split.payee_id"
            v-model:label="split.payee_label"
            :allow-create="true"
            custom-label="name"
            track-id="id"
            :placeholder="$t('Add a payee')"
            endpoint="/api/payees"
            class="w-full"
          />
        </AtField>
        <section>
            <AtField :label="$t(categoryLabel)" v-if="isTransfer || !fullHeight" class="md:block md:w-full">
              <!-- Required marker: category is mandatory on income + expense (not transfers). -->
              <template #action v-if="!isTransfer">
                <span class="font-bold text-error" aria-hidden="true">*</span>
              </template>
              <NSelect
                filterable
                clearable
                tag
                size="large"
                v-model:value="split[categoryField]"
                :default-expand-all="true"
                :options="categoryAccounts"
              />
            </AtField>
            <AtField :label="$t('Tags')" class="hidden md:block md:w-full">
                <LogerApiSimpleSelect
                    v-model="split.label_id"
                    v-model:label="split.label_name"
                    :allow-create="true"
                    class="w-full"
                    tag
                    custom-label="name"
                    track-id="id"
                    :placeholder="$t('Add label')"
                    endpoint="/api/labels"
                />
            </AtField>
        </section>
        <AtField :label="$t('Amount')" class="hidden md:block md:w-5/12">
          <InputMoney :number-format="true" v-model="split.amount" v-model:history="split.history" placeholder="">
            <template #prefix>
              <span class="flex items-center pl-2"> {{ currencySymbol }} </span>
            </template>
          </InputMoney>
        </AtField>
        <header v-if="fullHeight && !isTransfer" class="flex flex-col gap-2 px-0 py-2 md:flex-row md:items-start md:justify-between md:gap-3 md:px-4 md:py-3">
            <!-- Amount comes first on mobile (visual hero), category sits below as a row.
                 On desktop they sit side-by-side as before via md:order classes. -->
            <AtField v-if="!isPickerOpen" class="w-full md:order-2 md:w-auto md:flex-shrink-0">
              <InputMoney
                :number-format="true"
                v-model="split.amount"
                v-model:history="split.history"
                class="text-2xl font-bold md:text-base md:font-normal"
              >
                <template #prefix>
                  <span class="flex items-center pl-2 text-base text-body-1/60 md:text-current"> {{ currencySymbol }} </span>
                </template>
              </InputMoney>
            </AtField>

            <CategoryPicker
              class="w-full md:order-1 md:flex-1"
              v-model="split[categoryField]"
              v-model:isPickerOpen="isPickerOpen"
              :placeholder="$t('Choose {label}', { label: $t(categoryLabel) })"
              :options="categoryAccounts"
            />
          </header>
      </div>

      <footer class="flex justify-end mb-2" v-if="hasSplits">
        <AtField
            :label="$t('Description')"
            class="w-full md:mt-0"
        >
            <LogerInput v-model="split.concept" class="w-full" />
        </AtField>
        <div class="flex items-center justify-center">
            <span class="flex items-center h-10 px-4 mt-10  min-w-fit">
                {{  split.history }}
            </span>
        </div>
      </footer>

      <!-- Inline planned-transaction notice. Hidden for transfers (no category concept).
           Passive — appears the moment a category is selected, no click required. -->
      <section
        v-if="!isTransfer && relatedPlanned[index] && relatedPlanned[index].completion_status !== 'completed'"
        class="flex items-center justify-between gap-3 mt-2 px-3 py-2 text-sm rounded bg-secondary/10 border border-secondary/30"
      >
        <div class="flex items-center gap-2 text-secondary">
          <i class="fa fa-calendar-check" />
          <span>
            {{ $t('Planned') }} {{ formatMoney(relatedPlanned[index].total) }} —
            <span class="opacity-70">{{ $t('mark this as the planned transaction?') }}</span>
          </span>
        </div>
        <LogerButton
          variant="inverse-secondary"
          @click="markPlannedAsCompleted(index)"
        >
          {{ $t('Mark planned as paid') }}
        </LogerButton>
      </section>
    </section>

    <LogerButton
        v-if="!isTransfer"
        variant="ghost"
        class="mt-1"
        @click="addSplit()"
    >
      <IMdiCallSplit />
      {{ $t('Add split') }}
    </LogerButton>
  </section>
</template>
