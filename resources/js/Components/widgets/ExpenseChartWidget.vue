<script lang="ts" setup>
import { computed } from "vue";
import DonutChart from "../organisms/DonutChart.vue";
import exactMath from "exact-math";
import { formatMoney } from "@/utils";

const props = defineProps<{
  data: Record<string, number>[];
  componentProps: Record<string, string>;
  title?: string
}>();

const handleSelection = () => {};

const total = computed(() => {
  return props.data.reduce((total: number, item: Record<string, number>) => {
    return exactMath.add(total, item.total);
  }, 0);
});
</script>

<template>
    <DonutChart
      style="background: white; width: 100%"
      :series="data"
      :data="data"
      @clicked="handleSelection"
      v-bind="componentProps"
      label="name"
      value="total"
      :legend="false"
    />
    <section class="absolute text-center top-1/2 left-44  text-primary font-bold">
        <h4>
            {{  title  }}
        </h4>
        <h5>
            {{ formatMoney(total) }}
        </h5>
    </section>
</template>
