<script setup lang="ts">
import { useForm, usePage } from "@inertiajs/vue3";
import { reactive, toRefs, computed, watch } from "vue";
import { NSelect } from "naive-ui";
import { AtField, AtButton } from "atmosphere-ui";
import Slug from "slug";

import Modal from "@/Components/atoms/Modal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import { makeOptions } from "@/utils/naiveui";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { ref } from "vue";
import InputMoney from "@/Components/atoms/InputMoney.vue";
import { IAccount } from "../models";

const emit = defineEmits(["close"]);
const props = withDefaults(defineProps<{
    show: boolean;
    maxWidth?: string;
    closeable?: boolean;
    formData?: Partial<IAccount>;

}>(), {
    show:false,
    maxWidth: "2xl",
    closeable: true,
    formData:() => ({})
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
  }),
  accountDisplayId: computed(() => {
    return state.form.name && Slug(state.form.name, "_");
  }),
});

const modalTitle = computed(() => {
  return props.formData.id ? `Edit ${props.formData.name} account` : "Add Account";
});

watch(
  () => props.formData,
  (newValue) => {
    Object.keys(state.form.data()).forEach((field) => {
      if (field == "date") {
        state.form[field] = new Date(newValue[field]);
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
    console.time("started")
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
    console.timeEnd("actual Load")
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
</script>

<template>
  <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
    <div class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <div class="text-center sm:mt-0 sm:ml-4 sm:text-left">
        <header class="py-3 border-b">
          <h3 class="text-lg font-bold">
            <slot name="title">{{ modalTitle }}</slot>
          </h3>
        </header>

        <div class="px-4 pt-5 mt-2">
          <slot name="content">
            <div>
              <AtField
                label="Detail Type"
                class="flex justify-between w-full space-x-4 md:space-x-0"
                field-class="w-fullp"
              >
                <NSelect
                  filterable
                  clearable
                  tag
                  class="w-48 md:w-full"
                  v-model:value="form.account_detail_type_id"
                  :default-expand-all="true"
                  :options="detailOptions"
                />
              </AtField>

              <AtField
                label="Account Label"
                class="flex justify-between w-full space-x-4 md:space-x-0"
              >
                <LogerInput v-model="form.name" class="w-48 md:w-full" />
              </AtField>

              <AtField
                label="Closing day"
                class="flex justify-between w-full space-x-4 md:space-x-0"
                v-if="isCreditCard"
              >
                <LogerInput
                  v-model="form.credit_closing_day"
                  type="number"
                  class="w-48 md:w-full"
                />
              </AtField>

              <AtField
                label="Credit limit"
                class="flex justify-between w-full space-x-4 md:space-x-0"
                v-if="isCreditCard"
              >
                <InputMoney v-model="form.credit_limit" class="w-48 md:w-full" />
              </AtField>

              <AtField
                label="Opening Balance"
                class="flex justify-between w-full space-x-4 md:space-x-0"
                v-if="!form.id"
              >
                <LogerInput
                  v-model="form.opening_balance"
                  type="number"
                  class="w-48 md:w-full"
                />
              </AtField>
            </div>
          </slot>
        </div>
      </div>
    </div>

    <div
      class="flex w-full px-6 py-4 md:space-x-3 space-between bg-base-lvl-2"
      :class="[form.id ? 'space-between' : 'justify-end']"
    >
      <AtButton
        type="secondary"
        class="hidden text-danger md:block"
        @click="remove"
        rounded
        v-if="form.id"
        :disabled="form.processing"
      >
        Delete
      </AtButton>
      <div class="flex items-center justify-end w-full md:space-x-2">
        <AtButton
          type="secondary"
          class="hidden md:block"
          @click="close"
          rounded
          :disabled="form.processing"
        >
          Cancel
        </AtButton>
        <LogerButton
          class="w-full text-white md:w-fit bg-primary"
          @click="submit"
          rounded
          :processing="form.processing"
        >
          Save
        </LogerButton>
      </div>
    </div>
  </modal>
</template>
