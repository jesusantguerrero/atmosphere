<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :full-height="fullHeight"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="$emit('update:show', false)"
  >
    <div class="flex-1 pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header v-if="fullHeight" class="pb-4 text-lg font-bold border-b">
              Create a transaction
        </header>
        <TransactionTypesPicker  v-model="form.direction" />

        <div class="mt-2">
          <slot name="content">
            <div>
              <header v-if="fullHeight" class="flex justify-between px-4 py-3">
                <CategoryPicker
                  class="w-full"
                  v-model="form[categoryField]"
                  v-model:isPickerOpen="isPickerOpen"
                  :placeholder="`Choose ${categoryLabel}`"
                  :options="categoryOptions"
                />

                <AtField v-if="!isPickerOpen">
                  <LogerInput :number-format="true" v-model="form.total">
                    <template #prefix>
                      <span class="flex items-center pl-2"> RD$ </span>
                    </template>
                  </LogerInput>
                </AtField>
              </header>

              <div class="px-4 md:flex md:space-x-2 md:px-0">
                <AtField
                  label="Date"
                  class="flex justify-between w-full md:w-4/12 md:block"
                >
                  <NDatePicker
                    v-model:value="form.date"
                    type="date"
                    size="large"
                    class="w-48 md:w-full"
                  />
                </AtField>

                <AtField
                  label="Description"
                  class="flex justify-between w-full md:w-8/12 md:block md:space-x-0"
                >
                  <LogerInput v-model="form.description" class="w-48 md:w-full" />
                </AtField>
              </div>

              <TransactionItems
                ref="gridSplitsRef"
                :items="state.splits"
                :is-transfer="isTransfer"
                :categories="categoryOptions"
                :accounts="accountOptions"
              />
            </div>

            <div v-if="isRecurrence">
              <div class="flex">
                <AtField label="Repeat this transaction" class="w-full">
                  <NSelect
                    v-model:value="schedule_settings.frequency"
                    :options="[
                      {
                        value: 'WEEKLY',
                        label: 'Weekly',
                      },
                      {
                        value: 'MONTHLY',
                        label: 'Monthly',
                      },
                    ]"
                  />
                </AtField>
                <AtField :label="frequencyLabel">
                  <AtInput
                    type="number"
                    v-model="schedule_settings.repeat_on_day_of_month"
                  />
                </AtField>
              </div>
              <div class="flex">
                <AtField label="Ends" class="w-full">
                  <n-select
                    v-model:value="schedule_settings.end_type"
                    :options="[
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
                    ]"
                  />
                </AtField>
                <AtField label="Date" v-if="schedule_settings.end_type == 'DATE'">
                  <NDatePicker v-model:value="schedule_settings.end_date" size="lg" />
                </AtField>
                <AtField label="Instances" v-if="schedule_settings.end_type == 'COUNT'">
                  <AtInput type="number" v-model="schedule_settings.count" />
                </AtField>
              </div>
            </div>
          </slot>
        </div>
      </div>
    </div>

    <footer
      class="flex items-center justify-between w-full px-6 py-4 space-x-3 bg-base-lvl-2"
    >
      <section class="flex">
        <LogerButtonTab class="hidden rounded bg-base"> Use template</LogerButtonTab>
        <div class="flex hidden space-x-2">
          <AtFieldCheck v-model="isRecurrence" label="Set recurrence" />
        </div>
      </section>
      <div class="flex">
        <AtButton @click="close" rounded class="h-10 text-body"> Cancel </AtButton>
        <AtButton class="h-10 text-white bg-primary" @click="submit" rounded>
          Save
        </AtButton>
      </div>
    </footer>
  </modal>
</template>

<script setup>
import { format, parseISO } from "date-fns";
import { reactive, toRefs, watch, computed, inject, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { AtField, AtButton, AtFieldCheck, AtInput } from "atmosphere-ui";
import { NSelect, NDatePicker } from "naive-ui";

import Modal from "@/Components/atoms/Modal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import CategoryPicker from "./mobile/CategoryPicker.vue";
import TransactionTypesPicker from "./TransactionTypesPicker.vue";
import TransactionItems from "./TransactionItems.vue";

import { TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { cloneDeep } from "lodash";

const props = defineProps({
  show: {
    default: false,
  },
  maxWidth: {
    default: "2xl",
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
  splits: [],
  form: useForm({
    name: "",
    payee_id: "",
    payee_label: "",
    date: new Date(),
    is_transfer: false,
    description: "",
    direction: "WITHDRAW",
    category_id: null,
    counter_account_id: null,
    account_id: null,
    total: 0,
    has_splits: false,
  }),
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

const categoryLabel = computed(() => {
  return !isTransfer.value ? "Category" : "Destination";
});

const categoryField = computed(() => {
  return isTransfer.value ? "counter_account_id" : "category_id";
});

const categoryOptions = inject("categoryOptions", []);
const accountOptions = inject("accountsOptions", []);

const gridSplitsRef = ref();
const submit = () => {
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
  const splits = gridSplitsRef.value.getSplits();

  if (!splits.every((split) => split.amount)) {
    alert("Every split most have an amount");
    return;
  }

  state.form
    .transform((form) => {
      const data = {
        ...cloneDeep(form),
        resource_type_id: "MANUAL",
        total: form.total,
        date: format(new Date(form.date), "yyyy-MM-dd"),
        status: "verified",
        direction: form.is_transfer ? TRANSACTION_DIRECTIONS.WITHDRAW : form.direction,
        ...state.schedule_settings,
      };

      data.category_id = splits[0].category_id;
      data.payee_id = splits[0].payee_id;
      data.counter_account_id = splits[0].counter_account_id;
      data.account_id = splits[0].account_id;
      data.total = splits[0].amount;
      data.has_splits = false;

      if (splits.length > 1) {
        data.items = splits;
        data.has_splits = true;
        data.description = "Split with multiple categories";
      }

      return data;
    })
    .submit(action.method, action.url(), {
      onBefore(evt) {
        if (!evt.data.total) {
          alert("The balance should be more than 0");
        }
      },
      onSuccess: () => {
        emit("close");
        state.form.reset();
      },
    });
};

watch(
  () => props.transactionData,
  () => {
    const newValue = { ...props.transactionData };
    Object.keys(state.form.data()).forEach((field) => {
      if (field == "date" && newValue[field]) {
        const date = newValue[field]
        state.form[field] = parseISO(date);
      } else {
        state.form[field] = newValue[field] || state.form[field];
      }
    });

    state.splits = newValue.has_splits ? props.transactionData?.splits : [
        {
            payee_id: newValue.payee_id,
            payee_label: newValue.payee?.name,
            category_id: newValue.category_id,
            category: newValue.category,
            counter_account_id: null,
            account_id: newValue.account_id,
            account: newValue.account,
            amount: newValue.total,
        }
    ];
  },
  { deep: true }
);

watch(
  () => props.show,
  (show) => {
    state.form.direction = props.mode?.toUpperCase() ?? "EXPENSE";
  }
);

const isRecurrence = ref(props.recurrence);
const { form, schedule_settings } = toRefs(state);

const isPickerOpen = ref(false);
</script>
