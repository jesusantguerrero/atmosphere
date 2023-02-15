<template>
    <div :style="{width: '100%'}">
      <DoughnutChart ref="chartRef" :data="chartData" :options="options" />
    </div>
</template>

<script setup>
import { generateRandomColor } from "@/utils";
import { Chart, registerables } from "chart.js";
import { computed, ref, toRefs } from "vue";
import { Doughnut as DoughnutChart } from "vue-chartjs";

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
    default: true,
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

const emit = defineEmits("clicked");

const { series } = toRefs(props);

const chartData = computed(() => {
  return {
    labels: series.value.map((item) => item[props.label]),
    datasets: [
      {
        data: series.value.map((item) => item[props.value]),
        backgroundColor: series.value.map((item) => item.color || generateRandomColor()),
        borderJoinStyle: "mittr",
      },
    ],
  };
});

const chartRef = ref();
const options = computed(() => ({
  plugins: {
    ...(props.legend && {
      legend: {
        position: props.legendPosition,
      },
    }),
    title: {
      display: props.title,
      text: props.title,
    },
  },
  layout: {
    padding: 20,
  },
  onClick: (e, ...args) => {
    const chart = chartRef.value.chartInstance;
    const index = chart.tooltip.dataPoints[0].dataIndex;
    emit("clicked", index);
  },
}));
</script>
