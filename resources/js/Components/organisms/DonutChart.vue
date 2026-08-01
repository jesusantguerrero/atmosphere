
<script setup lang="ts">
import { nameToColor} from "@/utils";
import { Chart, registerables } from "chart.js/auto";
import { computed, ref, toRefs } from "vue";
import { Doughnut as DoughnutChart } from "vue-chartjs";

import { useDarkMode } from "@/composables/useDarkMode";

Chart.register(...registerables);
Chart.overrides['doughnut'].plugins.legend.display = false

const { isDark } = useDarkMode();
const themeColor = (token: string) => {
    const value = getComputedStyle(document.documentElement).getPropertyValue(token).trim();
    return value ? `rgb(${value})` : undefined;
};

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

const emit = defineEmits(["clicked"]);

const { series } = toRefs(props);

const chartData = computed(() => {
  return {
    labels: series.value.map((item) => item[props.label]),
    datasets: [
      {
        data: series.value.map((item) => item[props.value]),
        backgroundColor: series.value.map((item) => item.color || nameToColor(item[props.label])),
        borderJoinStyle: "mittr",
      },
    ],
  };
});

const chartRef = ref();
// Chart.js paints labels with its own `#666` default, which disappears against
// the dark surfaces. Re-read the theme token whenever the mode flips.
const labelColor = computed(() => (isDark.value, themeColor('--c-body-1')));
const options = computed(() => ({
  color: labelColor.value,
  plugins: {
    ...(props.legend && {
      legend: {
        position: props.legendPosition,
        labels: { color: labelColor.value },
      },
    }),
    responsive: true,
    title: {
      display: props.title,
      text: props.title,
    },
  },
  layout: {
    padding: 20,
  },
  onClick: (e, ...args) => {
    const chart = chartRef.value.chartInstance || args[1];
    const index = chart?.tooltip.dataPoints[0].dataIndex;
    emit("clicked", index);
  },
}));
</script>

<template>
    <div class="relative flex justify-center items-center min-h-96">
      <DoughnutChart
        ref="chartRef"
        class="absolute"
        :data="chartData"
        :options="options"
         width="100%"
         height="100%"
       />
      <div>
        <slot name="total">
            {{ total }}
        </slot>
      </div>
    </div>
</template>
