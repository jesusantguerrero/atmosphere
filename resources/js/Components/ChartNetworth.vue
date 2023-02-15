<template>
  <div class="w-full comparison-card">
    <div class="pb-10 px-5 rounded-lg">
      <h5 class="card-title text-left p-4 font-bold">{{ title }}</h5>
      <div class="card-text">
        <div class="comparison-header ic-scroller h-32 overflow-auto px-10 text-body-1/50 space-x-2 divide-x divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2 mb-2">
          <div
            v-for="header in state.headers"
            :key="header.id"
            @click="selectedDate = header.id"
            class="comparison-header__item min-w-max px-6 justify-center w-full items-center flex-col flex  previous-period cursor-pointer hover:text-body/80"
            >
            <h6 class="period-title">{{ header.label }}</h6>
            <span class="period-value text-sm" v-for="(value, index) in header.value">
                <div
                    class="absolute -left-4 top-2 h-2 w-2 rounded-full"
                    :style="{backgroundColor: state.options.colors[index]}"
                />
                <NumberHider />
                {{ formatMoney(value) }}
            </span>
          </div>
        </div>
        <LogerChart
            label="name"
            type="bar"
            :labels="currentSeries[0].labels"
            :options="state.options"
            :series="state.series"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import formatMoney from "@/utils/formatMoney";
import { format, parseISO } from "date-fns";
import { computed, reactive, ref } from "vue";

import LogerChart from "./organisms/LogerChart.vue";
import NumberHider from "./molecules/NumberHider.vue";

const props = defineProps({
    title: {
        type: String
    },
    type: {
      type: String,
      default: "bar"
    },
    data: {
      type: Object,
      required: true
    },
});

const formatMonth = (dateString) => {
    return format(parseISO(dateString), 'MMMM')
}

const selectedDate = ref()
const currentSeries = computed(() => {
    return [{
        name: 'Debts',
        data: Object.values(props.data).map(item => Math.abs(item.debts)),
        labels: Object.values(props.data).map(item => item.month_date)
    },
    {
        name: 'Assets',
        data: Object.values(props.data).map(item => item.assets),
        labels: Object.values(props.data).map(item => item.month_date)
    }];
})

const state = reactive({
    headers: Object.entries(props.data).map(([dateString, item]) => ({
        label: formatMonth(item.month_date),
        value: [item.debts, item.assets],
        id: item.month_date
    })),
    options: {
        colors: ["#7B77D1", "#F37EA1"],
    },
    series: currentSeries
});


</script>

<style lang="scss" scoped>
.comparison-card {
  border-radius: 0;
}

.comparison-header {
  display: flex;
  width: 100%;

  &__item {
    width: 50%;

    .period-value {
      position: relative;
      font-weight: bolder;
    }
  }
}
</style>
