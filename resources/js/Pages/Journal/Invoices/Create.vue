<template>
  <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
    <InvoiceTemplate
      ref="InvoiceTemplateForm"
      :is-editing="true"
      :clients="clients"
      :products="products"
      :invoice-data="invoice"
      :available-taxes="availableTaxes"
    />
  </div>
</template>

<script lang="ts" setup>
import { provide, ref } from "vue";
import InvoiceTemplate from "./Partials/InvoiceTemplate.vue";

const props = defineProps([
  "invoice",
  "clients",
  "products",
  "categories",
  "availableTaxes",
]);

const InvoiceTemplateForm = ref();
const saveForm = (isApplied: boolean) => {
  InvoiceTemplateForm.value.saveForm(isApplied);
};

provide("categories", props.categories);
</script>
