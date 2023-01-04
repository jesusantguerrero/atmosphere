<template>
  <component
    :is="chartComponent"
    :data="chartData"
    :options="options"
  />
</template>

<script setup>
import { computed, watch } from "vue";
import { Chart, registerables } from "chart.js";
import { Line, Bar } from "vue-chartjs";
import { formatMoney } from "@/utils";

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
    hasHiddenValues: {
        type: Boolean,
    }
});

const types = {
    bar: Bar,
    line: Line
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
  scales: {
    y: {
        ticks: {
            callback(value, index, ticks) {
                return props.hasHiddenValues ? '' : formatMoney(value)
            }
        }
    }
  },
  ...props.options
}));

Chart.register(...registerables);
watch(() => props.hasHiddenValues, () => {
}, { deep: true })

</script>
