<script setup lang="ts">
import { useForm, usePage, router } from "@inertiajs/vue3";
import { reactive, toRefs, computed, watch } from "vue";
import { NSelect } from "naive-ui";
import { AtField, AtButton, AtFieldCheck } from "atmosphere-ui";
import Slug from "slug";

import Modal from "@/Components/atoms/Modal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import { makeOptions } from "@/utils/naiveui";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { ref } from "vue";
import InputMoney from "@/Components/atoms/InputMoney.vue";
import CurrencySelector from "./CurrencySelector.vue";
import { IAccount } from "../models";
import { getCurrencyByCode } from '../currency-constants';

const emit = defineEmits(["close"]);
const props = withDefaults(defineProps<{
  show: boolean;
  maxWidth?: string;
  closeable?: boolean;
  formData?: Partial<IAccount>;

}>(), {
  show: false,
  maxWidth: "2xl",
  closeable: true,
  formData: () => ({})
});

const state = reactive({
  form: useForm({
    id: null,
    name: "",
    category_id: null,
    account_detail_type_id: null,
    display_id: "",
    description: "",
    opening_balance: 0,
    credit_closing_day: null,
    credit_limit: null,
    // Multi-currency fields
    currency_code: 'USD',
    is_multi_currency: false,
    secondary_currencies: [],
    currency_config: {}
  }),
  accountDisplayId: computed(() => {
    return state.form.name && Slug(state.form.name, "_");
  }),
});

// Multi-currency state
const newSecondaryCurrency = ref('');

const addSecondaryCurrency = () => {
  if (newSecondaryCurrency.value && !(state.form.secondary_currencies || []).includes(newSecondaryCurrency.value)) {
    if (!state.form.secondary_currencies) {
      state.form.secondary_currencies = [];
    }
    state.form.secondary_currencies.push(newSecondaryCurrency.value);
    newSecondaryCurrency.value = '';
  }
};

const removeSecondaryCurrency = (index: number) => {
  state.form.secondary_currencies.splice(index, 1);
};

const getCurrencyDisplay = (currencyCode: string) => {
  const currency = getCurrencyByCode(currencyCode);
  return currency ? `${currency.code} - ${currency.name}` : currencyCode;
};

const modalTitle = computed(() => {
  return props.formData.id ? `Edit ${props.formData.name} account` : "Add Account";
});

watch(
  () => props.formData,
  (newValue) => {
    Object.keys(state.form.data()).forEach((field) => {
      if (field == "date") {
        state.form[field] = new Date(newValue[field]);
      } else if (field === "secondary_currencies") {
        // Ensure secondary_currencies is always an array
        const value = newValue[field];
        if (typeof value === 'string') {
          // If it's a JSON string, parse it
          try {
            state.form[field] = JSON.parse(value);
          } catch {
            // If parsing fails, treat as empty array
            state.form[field] = [];
          }
        } else if (Array.isArray(value)) {
          state.form[field] = value;
        } else {
          state.form[field] = [];
        }
      } else {
        state.form[field] = newValue[field];
      }
    });
  },
  { deep: true, immediate: true }
);

const close = () => {
  emit("close");
};

const submit = () => {
  const actions = {
    save: {
      method: "post",
      url: () => route("accounts.store"),
    },
    update: {
      method: "put",
      url: () => route("accounts.update", props.formData),
    },
  };

  const method = props.formData && props.formData.id ? "update" : "save";
  const action = actions[method];
  state.form
    .transform((form) => {
      form.display_id = Slug(form.name, "_");
      return form;
    })
    .submit(action.method, action.url(), {
      onSuccess: ({ data }) => {
        emit("close");
        state.form.reset();
      },
    });
};

const closeAccount = (close: boolean) => {
  state.form.closed_at = close ? new Date() : null;
  state.form.archived = close;
  state.form.status = close ? "disabled" : "active";

  router.put(route("accounts.close", state.form), {
    closed_at: state.form.closed_at,
    archived: state.form.archived,
    status: state.form.status,
  }, {
    onSuccess: () => {
      emit("close");
      state.form.reset();
    },
  });
};

const remove = () => {
  if (confirm("Are you sure you want to delete this account?")) {
    state.form.delete(route("accounts.destroy", state.form), {
      onSuccess() {
        emit("close");
        state.form.reset();
      },
    });
  }
};

const detailTypes = usePage().props.accountDetailTypes;

const detailOptions = ref(makeOptions(detailTypes, ["id", "label"]));

const creditCard = computed(() => {
  return detailOptions.value.find((type) => type.label.toLowerCase() == "credit cards");
});

const isCreditCard = computed(() => {
  return state.form.account_detail_type_id == creditCard.value?.value;
});

const { form } = toRefs(state);

const excludedCurrencies = computed(() => {
  return [
    form.value.currency_code,
    ...(
      form.value.secondary_currencies
        ? form.value.secondary_currencies
        : [])
  ]
})
</script>

<template>
  <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
    <!-- Modal Header -->
    <div class="bg-base-lvl-3 border-b border-base-lvl-1">
      <div class="px-6 py-4">
        <h3 class="text-xl font-semibold text-body">
          <slot name="title">{{ modalTitle }}</slot>
        </h3>
      </div>
    </div>

    <!-- Modal Content -->
    <div class="bg-base-lvl-3 max-h-[70vh] overflow-y-auto">
      <div class="p-6">
        <slot name="content">
          <!-- Basic Account Information -->
          <div class="space-y-5">
            <!-- Account Type & Name Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <AtField label="Detail Type" class="space-y-2">
                <NSelect filterable clearable tag class="w-full" v-model:value="form.account_detail_type_id"
                  :default-expand-all="true" :options="detailOptions" />
              </AtField>

              <AtField label="Account Label" class="space-y-2">
                <LogerInput v-model="form.name" class="w-full" placeholder="Enter account name" />
              </AtField>
            </div>

            <!-- Credit Card Specific Fields -->
            <div v-if="isCreditCard"
              class="p-4 bg-base-lvl-2 rounded-lg border border-base-lvl-1 space-y-4">
              <div>
                <h4 class="text-sm font-medium text-body mb-3 flex items-center">
                  <svg class="w-4 h-4 mr-2 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM2 10v6a2 2 0 002 2h12a2 2 0 002-2v-6H2z" />
                  </svg>
                  Credit Card Settings
                </h4>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <AtField label="Closing Day" class="space-y-2">
                  <LogerInput v-model="form.credit_closing_day" type="number" class="w-full" placeholder="e.g., 15"
                    min="1" max="31" />
                </AtField>

                <AtField label="Credit Limit" class="space-y-2">
                  <InputMoney v-model="form.credit_limit" class="w-full" placeholder="0.00" />
                </AtField>
              </div>
            </div>

            <!-- Currency Configuration -->
            <div class="space-y-4">
              <h4 class="text-sm font-medium text-body flex items-center">
                <svg class="w-4 h-4 mr-2 text-primary" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z"
                    clip-rule="evenodd" />
                </svg>
                Currency Configuration
              </h4>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <AtField label="Primary Currency" class="space-y-2">
                  <CurrencySelector v-model="form.currency_code" placeholder="Select primary currency"
                    :clearable="false" class="w-full" />
                </AtField>

                <div class="flex items-end pb-1">
                  <AtFieldCheck label="Enable multi-currency support" v-model="form.is_multi_currency" />
                </div>
              </div>

              <!-- Multi-Currency Section -->
              <div v-if="form.is_multi_currency"
                class="mt-4 p-4 bg-gradient-to-br from-base-lvl-2 to-base-lvl-1 rounded-xl border border-base-lvl-1 shadow-sm space-y-4">
                <div class="flex items-center">
                  <svg class="w-4 h-4 mr-2 text-secondary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <h5 class="text-sm font-medium text-body">Secondary Currencies</h5>
                </div>

                <!-- Add Currency Input -->
                <div class="flex items-center gap-3">
                  <CurrencySelector v-model="newSecondaryCurrency" :exclude-currencies="excludedCurrencies"
                    placeholder="Select additional currency" class="flex-1" />
                  <AtButton @click="addSecondaryCurrency" :disabled="!newSecondaryCurrency" type="primary" size="small"
                    class="px-4 py-2 whitespace-nowrap">
                    Add Currency
                  </AtButton>
                </div>

                <!-- Selected Currencies List -->
                <div v-if="form.secondary_currencies?.length > 0" class="space-y-3">
                  <div class="text-xs font-medium text-body-1 uppercase tracking-wide">
                    Active Secondary Currencies ({{ form.secondary_currencies.length }})
                  </div>
                  <div class="grid gap-2">
                    <div v-for="(currency, index) in form.secondary_currencies" :key="currency"
                      class="flex items-center justify-between p-3 bg-base-lvl-3 rounded-lg border border-base-lvl-1 hover:border-primary/30 transition-colors">
                      <div class="flex items-center">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                          <span class="text-xs font-bold text-primary">{{ currency.substring(0, 2) }}</span>
                        </div>
                        <span class="font-medium text-body">{{ getCurrencyDisplay(currency) }}</span>
                      </div>
                      <AtButton @click="removeSecondaryCurrency(index)" type="secondary" size="small"
                        class="text-error hover:bg-error/10 px-3 py-1">
                        Remove
                      </AtButton>
                    </div>
                  </div>
                </div>

                <div v-else class="text-center py-6 text-body-1">
                  <svg class="w-12 h-12 mx-auto mb-3 text-body-1/50" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  <p class="text-sm">No secondary currencies added yet</p>
                  <p class="text-xs mt-1">Add currencies to enable multi-currency transactions</p>
                </div>
              </div>
            </div>

            <!-- Opening Balance -->
            <div v-if="!form.id">
              <AtField label="Opening Balance" class="space-y-2">
                <LogerInput v-model="form.opening_balance" type="number" class="w-full md:w-64" placeholder="0.00"
                  step="0.01" />
              </AtField>
            </div>

            <!-- Account Status -->
            <div v-if="form.id" class="pt-4 border-t border-base-lvl-1 space-y-4">
              <h4 class="text-sm font-medium text-body flex items-center">
                <svg class="w-4 h-4 mr-2 text-warning" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
                </svg>
                Account Status
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <AtFieldCheck label="Archive Account" v-model="form.archived" @update:model-value="closeAccount"
                  class="text-warning" />
                <AtFieldCheck label="Close Account" v-model="form.closed_at" @update:model-value="closeAccount"
                  class="text-error" />
              </div>
            </div>
          </div>
        </slot>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="bg-base-lvl-2 border-t border-base-lvl-1 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <AtButton v-if="form.id" type="secondary" @click="remove" :disabled="form.processing"
            class="text-error hover:bg-error/10 border-error/30">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete Account
          </AtButton>
        </div>

        <div class="flex items-center space-x-3">
          <AtButton type="secondary" @click="close" :disabled="form.processing" class="px-6">
            Cancel
          </AtButton>
          <LogerButton @click="submit" :processing="form.processing"
            class="bg-primary hover:bg-primaryDark text-white px-8 py-2 font-medium">
            <svg v-if="!form.processing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ form.id ? 'Update Account' : 'Create Account' }}
          </LogerButton>
        </div>
      </div>
    </div>
  </modal>
</template>
