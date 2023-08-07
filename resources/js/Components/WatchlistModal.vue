<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="emitClose"
  >
    <header class="flex items-center px-2 py-2 pl-6 font-bold bg-base-lvl-3">
      <button class="pl-0" @click="form.type = ''" v-if="form.type">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          xmlns:xlink="http://www.w3.org/1999/xlink"
          aria-hidden="true"
          role="img"
          class="iconify iconify--ic"
          width="32"
          height="32"
          preserveAspectRatio="xMidYMid meet"
          viewBox="0 0 24 24"
        >
          <path
            fill="currentColor"
            d="M14.71 6.71a.996.996 0 0 0-1.41 0L8.71 11.3a.996.996 0 0 0 0 1.41l4.59 4.59a.996.996 0 1 0 1.41-1.41L10.83 12l3.88-3.88c.39-.39.38-1.03 0-1.41z"
          ></path>
        </svg>
      </button>
      <span class="py-4"> Create Watchlist </span>
    </header>

    <section class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <TabSelector v-model="form.type" :options="options" v-if="!form.type" />
      <section v-else ref="importHolderRef">
        <AtField label="Name">
          <LogerInput v-model="form.name" />
        </AtField>

        <AtField label="Payees" v-if="isType('payees')">
          <LogerApiSimpleSelect
            v-model="form.input"
            v-model:label="form.payee_label"
            :multiple="true"
            custom-label="name"
            track-id="id"
            placeholder="Choose payees"
            endpoint="/api/payees"
          />
        </AtField>

        <AtField label="Categories" v-if="isType('categories')">
          <NSelect
            filterable
            clearable
            size="large"
            :multiple="true"
            v-model:value="form.input"
            :default-expand-all="true"
            :options="categoryOptions"
          />
        </AtField>
      </section>
    </section>

    <footer class="flex justify-end w-full px-6 py-4 space-x-3 text-right bg-base">
      <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
      <LogerButton variant="inverse" @click="submit" :disabled="!form.type">
        Create
      </LogerButton>
    </footer>
  </modal>
</template>

<script setup>
import { ref, inject } from "vue";
import { AtButton, AtField } from "atmosphere-ui";
import { useForm } from "@inertiajs/vue3";
import { NSelect } from "naive-ui";

import Modal from "@/Components/atoms/Modal.vue";
import ImportHolder from "@/Components/organisms/ImportHolder.vue";
import TabSelector from "./TabSelector.vue";
import LogerButtonTab from "./atoms/LogerButtonTab.vue";
import LogerButton from "./atoms/LogerButton.vue";
import LogerInput from "./atoms/LogerInput.vue";
import LogerApiSimpleSelect from "./organisms/LogerApiSimpleSelect.vue";

defineProps({
  show: {
    default: false,
  },
  maxWidth: {
    default: "2xl",
  },
  closeable: {
    default: true,
  },
});

const emit = defineEmits(["update:show"]);

const form = useForm({
  name: "",
  type: "",
  input: [],
});

const emitClose = () => {
  emit("update:show", false);
};

const submit = () => {
  form.submit("post", route("watchlist.store"), {
    onSuccess: emitClose,
  });
};

const options = [
  {
    value: "categories",
    label: "Category",
  },
  {
    value: "groups",
    label: "Category Group",
  },
  {
    value: "payees",
    label: "Payee",
  },
  {
    value: "tags",
    label: "Tag",
  },
];

const categoryOptions = inject("categoryOptions", []);

const isType = (typeName) => {
  return form.type == typeName;
};
</script>
