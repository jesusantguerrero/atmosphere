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
  reset() {
    splits.splice(0, splits.length, { ...defaultRow });
  }
});

const isPickerOpen = ref(false);

const showPlannedToggle = ref<{ [key: number]: boolean }>({});
const relatedPlanned = ref<{ [key: number]: any }>({});

async function fetchRelatedPlanned(index: number, split: SplitItem) {
  if (!split.category_id) return;
  // Replace with your actual API endpoint and params
  const res = await axios.get(`/api/planned?category_id=${split.category_id}`);
  relatedPlanned.value[index] = res.data;
}

async function markPlannedAsCompleted(index: number) {
  const planned = relatedPlanned.value[index];
  if (!planned) return;
  await axios.post(`/api/planned/${planned.id}/complete`);
  await fetchRelatedPlanned(index, splits[index]);
}
</script>


<template>
  <section>
    <section
      v-for="(split, index) in splits"
      :key="index"
      class="px-4 md:px-0 rounded-md even:bg-base-lvl-2"
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

      <div class="md:flex md:space-x-3 md:px-0 md:-mt-4">
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
            <AtField :label="categoryLabel" v-if="isTransfer || !fullHeight" class="md:block md:w-full">
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
            <AtField label="Tags" class="hidden md:block md:w-full">
                <LogerApiSimpleSelect
                    v-model="split.label_id"
                    v-model:label="split.label_name"
                    :allow-create="true"
                    class="w-full"
                    tag
                    custom-label="name"
                    track-id="id"
                    placeholder="Add label"
                    endpoint="/api/labels"
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
        <header v-if="fullHeight && !isTransfer" class="flex justify-between px-4 py-3">
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

      <!-- Toggle Button -->
      <LogerButton
        size="tiny"
        variant="neutral"
        class="mt-2"
        @click="() => {
          showPlannedToggle[index] = !showPlannedToggle[index];
          if (showPlannedToggle[index]) fetchRelatedPlanned(index, split);
        }"
      >
        {{ showPlannedToggle[index] ? 'Hide' : 'Show' }} Planned Transaction Options
      </LogerButton>

      <!-- Hidden Section -->
      <section v-if="showPlannedToggle[index]" class="mt-2 p-2 border rounded bg-base-lvl-2">
        <div v-if="relatedPlanned[index]">
          <p>
            There is a planned transaction for this category.<br>
            <strong>Planned Amount:</strong> {{ formatMoney(relatedPlanned[index].total) }}<br>
            <strong>Status:</strong> {{ relatedPlanned[index].completion_status || relatedPlanned[index].status }}
          </p>
          <LogerButton
            v-if="relatedPlanned[index].completion_status !== 'completed'"
            @click="markPlannedAsCompleted(index)"
            variant="success"
            class="mt-2"
          >
            Mark Planned as Completed
          </LogerButton>
        </div>
        <div v-else>
          <p>No planned transaction found for this category.</p>
        </div>
      </section>
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
