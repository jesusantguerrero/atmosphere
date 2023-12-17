<script setup lang="ts">
import InvoiceSimple from "./printTemplates/Simple.vue";

import { onMounted } from "vue";
import { IInvoiceWithRelations } from "@/Modules/invoicing/entities";
import ReportSimple from "./printTemplates/ReportSimple.vue";

interface IReport {
  title: string;
  description: string;
  startDate: Date;
  endDate: Date;
  type: string;
  content: Record<string, any>[];
}

withDefaults(
  defineProps<{
    user: Record<string, string>;
    invoice: IInvoiceWithRelations;
    businessData: Record<string, string>;
    type: string;
    invoiceTemplate: string;
    report?: IReport;
  }>(),
  {
    invoiceTemplate: "invoice-simple",
  }
);

onMounted(() => {
  // print();
  // close();
});
</script>

<template>
  <div class="preview-container">
    <InvoiceSimple
      :user="user"
      :type="type"
      :business-data="businessData"
      :invoice-data="invoice"
      id="invoice-content"
    />
  </div>
  <div class="preview-container" v-if="report">
    <ReportSimple
      :report-name="report.title"
      :description="report.description"
      :start-date="report.startDate"
      :end-date="report.endDate"
      :content="report.content"
      :business-data="businessData"
    />
  </div>
</template>

<style lang="scss">
body {
  background: #333 !important;
}

.preview-container {
  background: #333 !important;
  .simple-template {
    background-color: #333;
  }
  .section-body {
    background: white;
    max-width: 210mm;
    min-height: 265mm;
    width: 100%;
    height: auto;
    display: block;
    font-size: 12px;
  }

  tbody,
  .client-card {
    font-size: 12px;
  }
}

@media print {
  body {
    background: white !important;
  }

  .preview-container {
    background: white !important;
    .simple-template {
      background-color: white;
    }
    .section-body {
      background: white;
    }
  }
}
</style>
