<script setup lang="ts">
import { ref } from "vue";
import { AtButton } from "atmosphere-ui";

import Modal from "@/Components/atoms/Modal.vue";
import ImportHolder from "@/Components/organisms/ImportHolder.vue";
import TabSelector from "./TabSelector.vue";
import LogerButtonTab from "./atoms/LogerButtonTab.vue";
import LogerButton from "./atoms/LogerButton.vue";
import { useForm } from "@inertiajs/vue3";

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



const emitClose = () => {
  emit("update:show", false);
};

const options = [
  {
    value: route("budget.import"),
    label: "Budget",
  },
  {
    value: "/finance/import",
    label: "Transactions",
  },
  {
    value: route('housing.occurrences.import'),
    label: "Occurrence Checks",
  },
];

const selectedResource = ref("");

const formData = useForm({ file: "" })
const submit = ( ) => {
    formData.post(selectedResource.value, {
        onSuccess() {
          emitClose();
        }
    })
};
</script>


<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="emitClose"
  >
    <header class="flex items-center px-6 py-2 font-bold bg-base-lvl-3">
        <LogerButtonTab @click="selectedResource=''" v-if="selectedResource">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--ic" width="32" height="32" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M14.71 6.71a.996.996 0 0 0-1.41 0L8.71 11.3a.996.996 0 0 0 0 1.41l4.59 4.59a.996.996 0 1 0 1.41-1.41L10.83 12l3.88-3.88c.39-.39.38-1.03 0-1.41z"></path></svg>
        </LogerButtonTab>
        <span class="py-4">
            Import Transactions
        </span>
    </header>

    <section class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <TabSelector
        v-model="selectedResource"
        :options="options"
        v-if="!selectedResource"
      />
      <ImportHolder
        v-else
        v-model:file="formData.file"
        :endpoint="selectedResource"
        :processing="formData.processing"
      />
    </section>

    <footer class="flex justify-end px-6 py-4 space-x-3 bg-base" >
      <AtButton
        type="secondary"
        @click="close"
        rounded class="h-10"
        :disabled="formData.processing"
    >
        Cancel
    </AtButton>
      <LogerButton
        class="h-10 text-white bg-primary"
        @click="submit" rounded
        :disabled="!selectedResource || !formData.file || formData.processing"
        :processing="formData.processing"
        >
        Import
      </LogerButton>
    </footer>
  </modal>
</template>

