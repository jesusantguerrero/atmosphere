<template>
  <LineChart :chartData="chartData" :options="options" />
</template>

<script setup>
import { generateRandomColor } from "@/utils";
import { Chart, registerables } from "chart.js";
import { computed } from "vue";
import { LineChart, useLineChart } from "vue-chart-3";

Chart.register(...registerables);

const props = defineProps({
  series: {
    type: Array,
    default() {
      return [];
    },
  },
  labels: {
    type: Array,
    default: () => ([])
  },
  options: {
    type: Object,
    default: () => ({})
  },
  value: {
    type: String,
  },
  label: {
    type: String,
  },
  legendPosition: {
    type: String,
    default: "left",
  },
  scaleType: {
    default: "logarithmic",
  },
  scalePosition: {
    type: String,
    default: "left",
  },
});

const chartData = computed(() => {
  return {
    labels: props.labels,
    datasets:  props.series.map((item, index) => ({
        name: item.value,
        data: item.data,
        fill: true,
        backgroundColor: props.options.colors[index]
    })),
  };
});

const options = computed(() => ({
  plugins: {
    legend: {
      position: props.legendPosition,
    //   labels: {
    //       color: "white",
    //   }
    },
    title: {
      display: props.title,
      text: props.title,
    },
  },
  layout: {
    padding: 20,
  },
}));
</script>
