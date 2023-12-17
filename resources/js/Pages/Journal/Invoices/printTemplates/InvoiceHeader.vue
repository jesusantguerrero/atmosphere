<script setup lang="ts">
import { computed } from "vue";
import BusinessCard from "./BusinessCard.vue";
import InvoiceDetails from "./InvoiceDetails.vue";

const sizes = {
  sm: "h-16 w-16",
  md: "h-36 w-36",
  lg: "h-36 w-36",
};

const { invoiceTitle = "invoice", logoSize = "md", logoPosition = "left" } = defineProps<{
  logoUrl: string;
  logoOnly?: boolean;
  logoSize: "sm" | "md" | "lg";
  logoPosition: "left" | "right";
  displayCompanyInfo: boolean;
  businessData: Record<string, string>;
  invoiceTitle: string;
  invoiceNumber: string;
  invoiceDate: string;
  invoiceDueDate?: string;
  invoiceTitleClass: string;
  hideInvoiceDetails?: boolean;
}>();

const logoStyles = computed(() => {
  return sizes[logoSize];
});

const isReverse = computed(() => {
  return logoPosition == "right";
});
</script>

<template>
  <header class="pt-4 text-sm print:pt-0 invoice__header">
    <section
      class="flex w-full px-4 invoice-details"
      :class="[
        isReverse && !hideInvoiceDetails && 'flex-row-reverse',
        isReverse && hideInvoiceDetails ? 'justify-end' : 'justify-between',
      ]"
    >
      <article
        class="flex items-center"
        :class="[isReverse && hideInvoiceDetails ? 'w-fit' : 'w-full']"
      >
        <img :src="logoUrl" v-if="logoUrl" class="rounded-md" :class="[logoStyles]" />
        <BusinessCard
          :business="businessData"
          class="w-full text-left"
          v-if="displayCompanyInfo && !logoOnly"
        />
      </article>

      <InvoiceDetails
        class="mt-4"
        :class="[!isReverse ? 'justify-end' : 'justify-between']"
        v-if="!hideInvoiceDetails"
        :is-reverse="isReverse"
        :invoice-title-class="invoiceTitleClass"
        :invoice-title="invoiceTitle"
        :invoice-number="invoiceNumber"
        :invoice-date="invoiceDate"
        :invoice-due-date="invoiceDueDate"
      />
    </section>
  </header>
</template>
