<script setup lang="ts">
import { computed, watch, ref } from "vue";
import { Chart, registerables } from "chart.js";
import { Line, Bar } from "vue-chartjs";

const props = defineProps({
  type: {
    type: String,
    default: "line",
  },
  series: {
    type: Array,
    default() {
      return [];
    },
  },
  labels: {
    type: Array,
    default: () => [],
  },
  options: {
    type: Object,
    default: () => ({}),
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
  hasHiddenValues: {
    type: Boolean,
  },
  summaryLine: {
    type: Boolean,
  },
  summaryLineLabel: {
    type: String,
    default: "Summary"
  }
});

const emit = defineEmits(['clicked'])

const types = {
  bar: Bar,
  line: Line,
};

const chartComponent = computed(() => {
  return types[props.type];
});


function parseNumberToK(number: number) {
  if (number < 1000) {
    return number.toString(); // No need for 'k' if less than 1000
  } else {
    const kValue = Math.floor(number / 1000);
    const remainder = number % 1000;
    let result = kValue.toString();
    if (remainder > 0) {
      result += `.${Math.floor(remainder / 100)}`; // Append decimal point and first decimal
    }
    result += 'k';
    return result;
  }
}


const chartData = computed(() => {
    const dataSets = props.series.map((item, index) => ({
      label: item.name,
      data: item.data,
      fill: true,
      backgroundColor: props.options.colors[index],
      ...(props.options.borderColors && {
        borderColor: props.options.borderColors[index],
      }),
      ...item,
    }));

    const defaultColor = props.options.colors[0]

    return {
    labels: props.labels,
    datasets: [
        ...dataSets,
        ...(props.summaryLine ? [
            {
                label: props.summaryLineLabel,
                data: dataSets[0].data.map((value: number, index: number) => dataSets[1].data[index] - value),
                fill: false,
                pointStyle: 'circle',
                borderColor: defaultColor,
                backgroundColor: defaultColor,
                pointRadius: 4,
                pointHoverRadius: 8,
                type: 'line',
                order: 3
            }
        ] : [])
    ]
    };
});

const options = computed(() => ({
    interaction: {
      intersect: false,
      mode: 'index',
    },
  plugins: {
    title: {
      display: !!props.title,
      text: props.title,
    },
    ...props.options.plugins
  },
  layout: {
    padding: 20,
  },
  scales: {
    y: {
      ticks: {
        callback(value, index, ticks) {
          return props.hasHiddenValues ? "--" : parseNumberToK(value);
        },
      },
    },
  },
  onClick: (e, ...args) => {
    const chart = chartRef.value.chartInstance || args[1];
    const index = chart?.tooltip.dataPoints[0].dataIndex;
    emit("clicked", index);
  },
  ...props.options,
}));

const chartRef = ref();
const chartTypes = {
    'pie': 'pie',
    'bar': 'bar',
    'line': 'line'
};

const chartType = chartTypes[props.type] ?? 'bar';

Chart.register(...registerables);
if (Chart.overrides[chartType].plugins) {
  Chart.overrides[chartType].plugins.legend.display = false;
}
watch(
  () => props.hasHiddenValues,
  () => {
    chartRef.value.chart.options = options.value;
    chartRef.value.chart.update();
  },
  { deep: true }
);
</script>

<template>
  <component
    :is="chartComponent"
    :data="chartData"
    :options="options"
    ref="chartRef"
  />
</template>
