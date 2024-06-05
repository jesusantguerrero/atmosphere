<script setup lang="ts">
import SectionNav from "@/Components/SectionNav.vue";
import NextPaymentsWidget from "@/Pages/Loans/NextPaymentsWidget.vue";
import { ref } from "vue";

const selectedTab = ref("next");
const tabs = {
  next: {
    label: "Proximas cuotas",
  },
  overdue: {
    label: "Cuotas vencidas",
  },
};
</script>

<template>
  <section class="rounded-md overflow-hidden">
    <NextPaymentsWidget v-if="selectedTab == 'next'">
      <template #title>
        <SectionNav
          class="bg-base-lvl-3 w-full"
          selected-class="border-primary font-bold text-primary"
          v-model="selectedTab"
          :sections="tabs"
        />
      </template>
    </NextPaymentsWidget>
    <NextPaymentsWidget
      v-else
      title="Cuotas atrasadas"
      endpoint="/api/repayments?filter[payment_status]=late"
      method="back"
      default-range="All"
      date-field="payment_date"
      :ranges="[
        { label: 'All', value: null },
        { label: '90D', value: [90, 0] },
        { label: '30D', value: [30, 0] },
        { label: '7D', value: [7, 0] },
        { label: '1D', value: [1, 1] },
      ]"
    >
      <template #title>
        <SectionNav
          class="bg-base-lvl-3 w-full"
          selected-class="border-primary font-bold text-primary"
          v-model="selectedTab"
          :sections="tabs"
        />
      </template>
    </NextPaymentsWidget>
  </section>
</template>
