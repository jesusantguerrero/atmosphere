<script setup lang="ts">
import { inject } from "vue";
import { AtButton, AtField } from "atmosphere-ui";
import { useForm } from "@inertiajs/vue3";
import { NSelect } from "naive-ui";

import Modal from "@/Components/atoms/Modal.vue";
import LogerButton from "./atoms/LogerButton.vue";
import LogerInput from "./atoms/LogerInput.vue";
import LogerApiSimpleSelect from "./organisms/LogerApiSimpleSelect.vue";

const { maxWidth = '2xl', closeable = true, profileId }  = defineProps<{
  show: boolean;
  maxWidth: string;
  closeable: boolean;
  profileId: number;
}>();

const emit = defineEmits(["update:show"]);

const form = useForm({
  name: "",
  entity_type: "",
  entity_id: null,
});

const emitClose = () => {
  emit("update:show", false);
};

const submit = () => {
  form.transform((data) => ({
    ...data
  })).submit("post", `/loger-profiles/${profileId}/entities`, {
    onSuccess: emitClose,
  });
};

const options = [
  {
    value: "category",
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

const isType = (typeName: string) => {
  return form.entity_type == typeName;
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
    <header class="flex items-center px-2 py-2 pl-6 font-bold bg-base-lvl-3">
      <span class="py-4"> Link entity </span>
    </header>

    <section class="pb-4 bg-base-lvl-3 sm:p-6 sm:pb-4 text-body">
      <AtField label="Name">
        <LogerInput v-model="form.name" />
      </AtField>
      <Multiselect
            v-model="form.Type"
            @update:model-value="form.entity_type = $event.value"
            :options="options"
            placeholder="Type to search"
            track-by="value"
            label="label"
            select-label=""
       />
      <section>
        <AtField label="Payees" v-if="isType('payees')">
          <LogerApiSimpleSelect
            v-model="form.entity_id"
            v-model:label="form.payee_label"
            :multiple="true"
            custom-label="name"
            track-id="id"
            placeholder="Choose payees"
            endpoint="/api/payees"
          />
        </AtField>

        <AtField label="Category" v-if="isType('category')">
          <NSelect
            filterable
            clearable
            size="large"
            v-model:value="form.entity_id"
            :default-expand-all="true"
            :options="categoryOptions"
          />
        </AtField>
      </section>
    </section>

    <footer class="flex justify-end w-full px-6 py-4 space-x-3 text-right bg-base">
      <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
      <LogerButton variant="inverse" @click="submit" :disabled="!form.entity_type">
        Create
      </LogerButton>
    </footer>
  </modal>
</template>
