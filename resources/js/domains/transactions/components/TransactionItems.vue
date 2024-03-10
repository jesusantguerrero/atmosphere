<script setup lang="ts">
import { reactive, computed, inject, ref } from "vue";
import { AtField } from "atmosphere-ui";
import { NSelect } from "naive-ui";

import InputMoney from "@/Components/atoms/InputMoney.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
import CategoryPicker from "./CategoryPicker.vue";
import { IAccount, ICategory } from "../models";
import { formatMoney } from "@/utils";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import { TRANSACTION_DIRECTIONS } from "..";

interface SplitItem {
    payee_id: null|number,
    payee_label: string,
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
}>();
const accountLabel = computed(() => {
  return !props.isTransfer ? "Account" : "Source";
});
const categoryLabel = computed(() => {
  return !props.isTransfer ? "Category" : "Destination";
});

const categoryField = computed(() => {
  return props.isTransfer ? "counter_account_id" : "category_id";
});

const categoryOptions = inject("categoryOptions", []);
const accountsOptions = inject("accountsOptions", []);

const categoryAccounts = computed(() => {
    if (props.isTransfer) {
        return accountsOptions;

    } else {
        return categoryOptions.filter((category) => {
            const isInflow = props.mode == TRANSACTION_DIRECTIONS.DEPOSIT;
            return (isInflow && category.display_id == "inflow") || !isInflow; 
        });
    }
});

const splits = reactive<SplitItem[]>(props.items ?? []);
const hasSplits = computed(() => splits.length > 1);

const defaultRow = {
    payee_id: null,
    payee_label: "",
    date: null,
    description: "",
    category_id: null,
    counter_account_id: null,
    account_id: null,
    amount: 0,
    history: 0
};

const splitsTotal = computed(() =>
    splits.reduce((total: number, splitItem): number => {
        return total + parseFloat(splitItem.amount ?? "0");
    }, 0)
)

const addSplit = () => {
  splits.push({...defaultRow});
};

const removeSplit = (index: number) => {
  splits.splice(index, 1);
};

if (!props.items?.length) {
  addSplit();
}

defineExpose({
  getSplits() {
    return splits;
  },
  reset() {
    const items = props.items.at?.(0) ?? structuredClone(defaultRow)
    splits.splice(0, splits.length, {...items})
  }
});

const isPickerOpen = ref(false);
</script>


<template>
  <section>
    <section
      v-for="(split, index) in splits"
      :key="index"
      class="px-4 -mx-4 rounded-md even:bg-base-lvl-2"
    >


      <section v-if="!index">
          <AtField
            :label="accountLabel"
            class="flex justify-between w-full space-x-4 md:w-full md:my-0 md:block md:space-x-0 md:-mt-4"
          >
            <NSelect
              filterable
              clearable
              tag
              size="large"
              class="w-48 md:w-full"
              v-model:value="split.account_id"
              :default-expand-all="true"
              :options="accountsOptions"
            />
          </AtField>

        <div class="px-2 py-1 text-center" v-if="hasSplits">
            {{  formatMoney(splitsTotal) }}
        </div>
      </section>

      <header class="flex justify-between pt-2 -mb-4" v-if="hasSplits">
        <h4 class="font-bold">Split ({{ index + 1 }}/{{ splits.length }})</h4>
        <button @click="removeSplit(index)">
          <IMdiTrash />
        </button>
      </header>

      <div class="px-4 md:flex md:space-x-3 md:px-0 md:-mt-4">
        <AtField
          label="Payee"
          class="flex justify-between md:w-4/12 md:block md:space-x-0"
          v-if="!isTransfer"
        >
          <LogerApiSimpleSelect
            v-model="split.payee_id"
            v-model:label="split.payee_label"
            :allow-create="true"
            custom-label="name"
            track-id="id"
            placeholder="Add a payee"
            endpoint="/api/payees"
            class="w-48 md:w-full"
          />
        </AtField>
        <section>
            <AtField :label="categoryLabel" class="hidden md:block md:w-full">
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
        </section>
        <AtField label="Amount" class="hidden md:block md:w-5/12">
          <InputMoney :number-format="true" v-model="split.amount" v-model:history="split.history">
            <template #prefix>
              <span class="flex items-center pl-2"> RD$ </span>
            </template>
          </InputMoney>
        </AtField>
        <header v-if="fullHeight" class="flex justify-between px-4 py-3">
            <CategoryPicker
              class="w-full"
              v-model="split[categoryField]"
              v-model:isPickerOpen="isPickerOpen"
              :placeholder="`Choose ${categoryLabel}`"
              :options="categoryAccounts"
            />

            <AtField v-if="!isPickerOpen">
              <InputMoney :number-format="true" v-model="split.amount" v-model:history="split.history">
                <template #prefix>
                  <span class="flex items-center pl-2"> RD$ </span>
                </template>
              </InputMoney>
            </AtField>
          </header>
      </div>

      <footer class="flex justify-end mb-2" v-if="hasSplits">
        <AtField
            label="Description"
            class="flex justify-between w-full space-x-2 md:block md:space-x-0 md:mt-0"
        >
            <LogerInput v-model="split.concept" class="w-48 md:w-full" />
        </AtField>
        <div class="flex items-center justify-center">
            <span class="flex items-center h-10 px-4 mt-10  min-w-fit">
                {{  split.history }}
            </span>
        </div>
      </footer>
    </section>

    <LogerButton 
        v-if="!isTransfer"
        variant="neutral" 
        @click="addSplit()" 
    >
      <IMdiCallSplit />
      Add split
    </LogerButton>
  </section>
</template>
