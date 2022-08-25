<template>
  <modal
    :show="show"
    :max-width="maxWidth"
    :closeable="closeable"
    v-slot:default="{ close }"
    @close="emitClose"
  >
    <NSelect v-model:value="selectedResource" :options="options" />
    <div class="pb-4 bg-base-lvl-1 sm:p-6 sm:pb-4 text-body">
      <ImportHolder
        ref="importHolderRef"
        @uploaded="emitClose"
        :endpoint="selectedResource"
      />
    </div>

    <div class="px-6 py-4 space-x-3 text-right bg-base">
      <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
      <AtButton class="text-white bg-primary h-10" @click="submit" rounded>
        Import
      </AtButton>
    </div>
  </modal>
</template>

<script setup>
import Modal from "@/Jetstream/Modal.vue";
import ImportHolder from "@/Components/organisms/ImportHolder.vue";
import { AtButton} from "atmosphere-ui";
import { NSelect } from "naive-ui"
import { ref } from "vue";

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

const importHolderRef = ref();
const submit = () => {
  importHolderRef.value.processImport();
};

const emitClose = () => {
  emit("update:show", false);
};

const options = [{
    value: '/budgets/import',
    label: 'Budget'
}, {
    value: '/finance/import',
    label: 'Transactions'
}]

const selectedResource = ref(options[1].value)
</script>
