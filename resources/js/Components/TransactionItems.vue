<template>
  <div>
    <div
      v-for="(split, index) in splits"
      :key="index"
      class="px-4 -mx-4 rounded-md even:bg-base-lvl-2"
    >
      <header class="flex justify-between pt-2 -mb-4" v-if="hasSplits">
        <h4 class="font-bold">Split ({{ index + 1 }}/{{ splits.length }})</h4>
        <button @click="removeSplit(index)">
          <IMdiTrash />
        </button>
      </header>
      <AtField
        :label="accountLabel"
        v-if="!index"
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
      <div class="px-4 md:flex md:space-x-3 md:px-0 md:-mt-4">
        <AtField
          label="Payee"
          class="flex justify-between md:w-4/12 md:block md:space-x-0"
          v-if="!isTransfer"
        >
          <LogerApiSimpleSelect
            v-model="split.payee_id"
            v-model:label="split.payee_label"
            tag
            custom-label="name"
            track-id="id"
            placeholder="Add a payee"
            endpoint="/api/payees"
            class="w-48 md:w-full"
          />
        </AtField>
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
        <AtField label="Amount" class="hidden md:block md:w-5/12">
          <InputMoney :number-format="true" v-model="split.amount">
            <template #prefix>
              <span class="flex items-center pl-2"> RD$ </span>
            </template>
          </InputMoney>
        </AtField>
      </div>
    </div>
    <LogerButton variant="neutral" @click="addSplit()" v-if="!isTransfer">
      <IMdiCallSplit />
      Add split
    </LogerButton>
  </div>
</template>

<script setup lang="ts">
import { reactive, computed, inject } from "vue";
import { AtField } from "atmosphere-ui";
import { NSelect } from "naive-ui";

import InputMoney from "@/Components/atoms/InputMoney.vue";
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
import LogerButton from "./atoms/LogerButton.vue";

const props = defineProps({
  items: {
    type: Array,
    default: [],
  },
  categories: {
    type: Array,
    default: [],
  },
  accounts: {
    type: Array,
    default: [],
  },
  isTransfer: {
    type: Boolean,
  },
});
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
  return props.isTransfer ? accountsOptions : categoryOptions;
});

const splits = reactive(props.items ?? []);
const hasSplits = computed(() => splits.length > 1);

const defaultRow = {
    payee_id: "",
    payee_label: "",
    date: null,
    description: "",
    category_id: null,
    counter_account_id: null,
    account_id: null,
    amount: 0,
};

const addSplit = () => {
  splits.push({...defaultRow});
};

const removeSplit = (index: number) => {
  splits.splice(index, 1);
};

if (!props.items.length) {
  addSplit();
}

defineExpose({
  getSplits() {
    return splits;
  },
  reset() {
    splits.splice(0, splits.length, {...defaultRow})
  }
});
</script>
