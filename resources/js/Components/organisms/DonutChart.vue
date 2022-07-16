<template>
  <DoughnutChart :chartData="chartData" :options="options" />
</template>

<script setup>
import { Chart, registerables } from "chart.js";
import { computed } from "vue";
import { DoughnutChart, useDoughnutChart } from "vue-chart-3";
Chart.register(...registerables);

const props = defineProps({
  series: {
    type: Array,
    default() {
      return [];
    },
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

const generateRandomColor = () => `#${Math.floor(Math.random() * 16777215).toString(16)}`;
const chartData = computed(() => {
  return {
    labels: props.series.map((item) => item[props.label]),
    datasets: [
      {
        data: props.series.map((item) => item[props.value]),
        backgroundColor: props.series.map(() => generateRandomColor()),
        borderJoinStyle: "mittr",
      },
    ],
  };
});

const options = computed(() => ({
  plugins: {
    legend: {
      position: props.legendPosition,
      labels: {
          color: "white",
      }
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
