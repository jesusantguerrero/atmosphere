<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
// @ts-ignore
import { AtField } from "atmosphere-ui";
import { getStatus, getStatusColor, getStatusIcon } from "@/Modules/invoicing/constants";

import AccountingSectionNav from "@/Pages/Journal/Partials/AccountingSectionNav.vue";
import InvoiceSimple from "./printTemplates/Simple.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";

import { ref } from "vue";

defineProps({
  invoice: {
    type: Object,
    required: true,
  },
  businessData: {
    type: Object,
    required: true,
  },
  type: {
    type: String,
    required: true,
  },
  user: {
    type: Object,
  },
  invoiceTemplate: {
    type: String,
    default: "invoice-simple",
  },
});

const isModalPrintOpen = ref(false);
const print = () => {
  const modalInvoice = document.getElementById("invoice-content");
  const cloned = modalInvoice?.cloneNode(true);
  let section = document.getElementById("print");
  isModalPrintOpen.value = true;

  if (!section) {
    section = document.createElement("div");
    section.id = "print";
    document.body.appendChild(section);
  }

  section.innerHTML = "";
  if (cloned) {
    section.appendChild(cloned);
    window.print();
  }
  // isModalPrintOpen.value = true;
};
</script>

<template>
  <AppLayout :title="invoice.concept">
    {{ invoice }}
  </AppLayout>
</template>
