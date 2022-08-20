<template>
  <DoughnutChart ref="chartRef" :chartData="chartData" :options="options" />
</template>

<script setup>
import { generateRandomColor } from "@/utils";
import { Chart, registerables } from "chart.js";
import { getRelativePosition } from "chart.js/helpers";
import { computed, ref } from "vue";
import { DoughnutChart } from "vue-chart-3";
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
  legend: {
    type: Boolean,
    default: true
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


const emit = defineEmits('clicked')
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


const chartRef = ref()
const options = computed(() => ({
    plugins: {
        ...(props.legend && { legend: {
        position: props.legendPosition,
        }}),
        title: {
        display: props.title,
        text: props.title,
        },
    },
    layout: {
        padding: 20,
    },
    onClick: (e, ...args) => {
        const chart = chartRef.value.chartInstance
        console.log(chart.tooltip.dataPoints)
        const index = chart.tooltip.dataPoints[0].dataIndex;
       emit('clicked', index)

    }
}));
</script>
