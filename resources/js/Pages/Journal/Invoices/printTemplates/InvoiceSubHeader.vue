<script setup lang="ts">
import BusinessCard from "./BusinessCard.vue";
import ClientCard from "./InvoiceClientCard.vue";
import InvoiceTotalItem from "../Partials/InvoiceTotalItem.vue";
import InvoiceDetails from "./InvoiceDetails.vue";

interface SubHeaderCard {
  type: "ContactInfo" | "TotalInfo" | "BusinessInfo";
}

const componentTypes = {
  ContactInfo: ClientCard,
  BusinessInfo: BusinessCard,
  InvoiceTotalItem,
  InvoiceDetails,
};

defineProps<{
  cards: SubHeaderCard[];
}>();

const getComponent = (typeName: string) => {
  return componentTypes[typeName];
};
</script>

<template>
  <section class="flex justify-between pt-4 text-sm print:pt-0">
    <component
      :is="getComponent(card.type)"
      v-for="(card, index) in cards"
      v-bind="card"
      :key="index"
    />
  </section>
</template>
