<script lang="ts" setup>
import { formatMoney } from "@/utils";

interface Props {
  chart: {
    series: any[];
    options: Record<string, any>;
  };
  title?: string;
  description?: string;
  sections?: any[];
}
defineProps<Props>();
</script>

<template>
  <div
    class="flex flex-col h-full overflow-hidden bg-white divide-x-2 lg:flex-row sm:rounded-lg"
  >
    <article class="p-5 h-full" :class="[sections ? 'lg:w-9/12' : 'w-full']">
      <header>
        <h4 class="font-bold">{{ title }}</h4>
        <small>{{ description }}</small>
      </header>
      <div class="w-full h-full mx-auto pb-10">
        <apexchart
          ref="apexchart"
          width="100%"
          height="100%"
          type="line"
          class="mx-auto"
          :options="chart.options"
          :series="chart.series"
        />
      </div>
    </article>
    <article class="p-5 lg:w-3/12" v-if="sections">
      <header>
        <h4 class="font-bold">Reports</h4>
        <small>Avg.</small>
      </header>
      <section class="mt-4 space-y-2">
        <div v-for="section in sections" class="p-2 bg-gray-100 rounded-md">
          <h4 class="text-xs">{{ section.display_id }}</h4>
          <p class="font-bold">{{ formatMoney(section.total) }}</p>
        </div>
      </section>
    </article>
  </div>
</template>
