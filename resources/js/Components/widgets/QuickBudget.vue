<template>
  <section class="space-y-2 border border-transparent rounded bg-base-lvl-3 p-4 shadow-xl">
    <header class="flex justify-between items-center">
      <SectionTitle>Quick Budgets</SectionTitle>
      <span class="text-secondary font-bold text-sm"></span>
    </header>
    <article class="flex">
        <TabSelector class="w-full"
        v-model="selectedResource"
        :options="options"
        v-if="!selectedResource"
      />
    </article>
  </section>
</template>

<script setup>
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import { ref } from "vue";
import TabSelector from "../TabSelector.vue";

const options = [
  {
    value: "/budgets/import",
    label: "Copy from last month",
  },
  {
    value: "/finance/import",
    label: "Reset all leftovers",
  },
];

const selectedResource = ref("");
</script>
