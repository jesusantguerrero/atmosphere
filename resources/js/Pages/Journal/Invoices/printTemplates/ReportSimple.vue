<script setup lang="ts">
import AtTable from "@/Components/shared/BaseTable.vue";
import BusinessCard from "./BusinessCard.vue";
import cols from "./cols";

withDefaults(
  defineProps<{
    reportName: string;
    description: string;
    startDate: Date;
    endDate: Date;
    content: any;
    imageUrl: string;
    businessData: Record<string, string>;
  }>(),
  {
    imageUrl: "/logo.png",
  }
);
</script>

<template>
  <section class="section-body">
    <header class="pt-5 px-5 flex justify-between">
      <article>
        <h1 class="text-2xl font-bold text-primary mt-4">{{ reportName }}</h1>
        <h3 class="text-lg text-body-1 font-bold">{{ description }}</h3>
        <h4 class="mt-4">
          <span class="capitalize"> {{ $t("from") }} {{ startDate }} </span>
          <span class="capitalize ml-2"> {{ $t("to") }} {{ endDate }} </span>
        </h4>
      </article>
      <article class="flex items-center flex-col w-72">
        <img :src="imageUrl" v-if="imageUrl" class="w-24 rounded-md" />
        <BusinessCard :business="businessData" class="w-full text-left" />
      </article>
    </header>

    <main>
      <AtTable v-if="content.type == 'table'" :table-data="content.data" :cols="cols" />
    </main>
  </section>
</template>
