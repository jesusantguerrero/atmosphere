<template>
  <AppLayout title="Factura">
    <template #header>
      <AccountingSectionNav>
        <template #actions>
          <AppButton @click="saveForm(true)" variant="inverse">
            {{ $t("save invoice") }}
          </AppButton>
        </template>
      </AccountingSectionNav>
    </template>

    <div class="py-10 mx-auto sm:px-6 lg:px-8">
      <InvoiceTemplate
        ref="InvoiceTemplateForm"
        :is-editing="true"
        :type="type"
        :clients="clients"
        :products="products"
        :invoice-data="invoice"
        :available-taxes="availableTaxes"
      />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";

import AppLayout from "@/Components/templates/AppLayout.vue";
import InvoiceTemplate from "./Partials/InvoiceTemplate.vue";
import AccountingSectionNav from "../Partials/AccountingSectionNav.vue";
import AppButton from "../../../Components/shared/AppButton.vue";

const props = defineProps([
  "invoice",
  "clients",
  "products",
  "categories",
  "availableTaxes",
  "type",
]);

const InvoiceTemplateForm = ref();
const saveForm = (isApplied: boolean) => {
  InvoiceTemplateForm.value.saveForm(isApplied);
};

provide("categories", props.categories);
</script>
