<template>
  <component
    :is="chartComponent"
    :chartData="chartData"
    :options="options"
  />
</template>

<script setup>
import { Chart, registerables } from "chart.js";
import { computed } from "vue";
import { LineChart, BarChart } from "vue-chart-3";

const props = defineProps({
    type: {
        type: String,
        default: 'line'
    },
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
        default: "",
    },
    scaleType: {
        default: "logarithmic",
    },
    scalePosition: {
        type: String,
        default: "left",
    },
});

const types = {
    bar: BarChart,
    line: LineChart
};

const chartComponent = computed(() => {
    return types[props.type]
})

const chartData = computed(() => {
  return {
    labels: props.labels,
    datasets:  props.series.map((item, index) => ({
        label: item.name,
        data: item.data,
        fill: true,
        backgroundColor: props.options.colors[index],
        ...(props.options.borderColors && {borderColor: props.options.borderColors[index]}),
        ...item,
    })),
  };
});

const options = computed(() => ({
  plugins: {
    title: {
      display: !!props.title,
      text: props.title,
    },
  },
  layout: {
    padding: 20,
  },
  ...props.options
}));

Chart.register(...registerables);
</script>
