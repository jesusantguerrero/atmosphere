<template>
  <div>
    <div
      v-for="(split, index) in splits"
      :key="index"
      class="rounded-md odd:bg-base-lvl-2"
    >
      <header class="flex justify-between" v-if="hasSplits">
        <h4>Split ({{ index + 1 }} / {{ splits.length }})</h4>
        <button @click="removeSplit(index)">
          <IMdiTrash />
        </button>
      </header>
      <AtField
        :label="accountLabel"
        class="md:w-full md:my-0 md:block md:space-x-0 flex w-full justify-between space-x-4 md:-mt-4"
      >
        <NSelect
          filterable
          clearable
          tag
          size="large"
          class="w-48 md:w-full"
          v-model:value="split.account_id"
          @update:value="createCategory"
          :default-expand-all="true"
          :options="accountsOptions"
        />
      </AtField>
      <div class="md:flex md:space-x-3 md:px-0 px-4 md:-mt-4">
        <AtField
          label="Payee"
          class="md:w-4/12 md:block md:space-x-0 flex justify-between"
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
            @update:value="createCategory"
            :default-expand-all="true"
            :options="categoryAccounts"
          />
        </AtField>
        <AtField label="Amount" class="hidden md:block md:w-5/12">
          <LogerInput :number-format="true" v-model="split.total">
            <template #prefix>
              <span class="flex items-center pl-2"> RD$ </span>
            </template>
          </LogerInput>
        </AtField>
      </div>
    </div>
    <button @click="addSplit()">Add split</button>
  </div>
</template>

<script setup lang="ts">
import { reactive, toRefs, computed, inject } from "vue";
import { AtField } from "atmosphere-ui";
import { NSelect } from "naive-ui";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";

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

const emit = defineEmits(["close", "saved"]);

const state = reactive({
  frequencyLabel: "every",
});

const accountLabel = computed(() => {
  return !props.isTransfer ? "Account" : "Source";
});
const categoryLabel = computed(() => {
  return !props.isTransfer ? "Category" : "Destination";
});

const categoryField = computed(() => {
  return !props.isTransfer ? "counter_account_id" : "category_id";
});

const categoryOptions = inject("categoryOptions", []);
const accountsOptions = inject("accountsOptions", []);

const categoryAccounts = computed(() => {
  return !props.isTransfer ? accountsOptions : categoryOptions;
});

const splits = reactive([]);
const hasSplits = computed(() => splits.length > 1);

const addSplit = () => {
  splits.push({
    payee_id: "",
    payee_label: "",
    date: null,
    description: "",
    direction: "WITHDRAW",
    category_id: null,
    counter_account_id: null,
    account_id: null,
    total: 0,
  });
};

const removeSplit = (index: number) => {
  splits.splice(index, 1);
};

addSplit();

defineExpose({
  getSplits() {
    return splits;
  },
});
</script>
