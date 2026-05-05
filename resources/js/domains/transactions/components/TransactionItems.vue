<script setup lang="ts">
import { reactive, computed, inject, ref, watch } from "vue";
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
          <InputMoney :number-format="true" v-model="split.amount" v-model:history="split.history" placeholder="">
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
        variant="neutral"
        @click="addSplit()"
    >
      <IMdiCallSplit />
      Add split
    </LogerButton>
  </section>
</template>
