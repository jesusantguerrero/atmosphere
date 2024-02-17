<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";
import { format, parseISO } from "date-fns";
import { computed, reactive, ref } from "vue";

import LogerChart from "./organisms/LogerChart.vue";
import ChartHeaderScroller from "./ChartHeaderScroller.vue";
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
        labels: Object.values(props.data).map(item => item.date_unit)
    },
    {
        name: 'Assets',
        data: Object.values(props.data).map(item => item.assets),
        labels: Object.values(props.data).map(item => item.date_unit)
    }];
})

const state = reactive({
    headers: Object.entries(props.data).map(([dateString, item]) => ({
        label: item.date_unit,
        value: [item.debts, item.assets],
        id: item.date_unit
    })),
    options: {
        colors: ["#7B77D1", "#F37EA1"],
    },
    series: currentSeries
});


</script>

<template>
  <div class="w-full comparison-card">
    <div class="pb-10 rounded-lg">
      <div class="card-text" >
        <ChartHeaderScroller item-class="comparison-header__item" class="flex flex-row-reverse">
            <section
                v-for="header in state.headers"
                :key="header.id"
                @click="selectedDate = header.id"
                class="flex flex-col items-center justify-center w-full px-6 cursor-pointer select-none comparison-header__item snap-center min-w-max previous-period hover:text-body/80"
            >
            <h6 class="period-title">{{ header.label }}</h6>
            <span class="text-sm period-value" v-for="(value, index) in header.value">
                <div
                    class="absolute w-2 h-2 rounded-full -left-4 top-2"
                    :style="{backgroundColor: state.options.colors[index]}"
                />
                <NumberHider />
                {{ formatMoney(value) }}
            </span>
        </section>
        </ChartHeaderScroller>
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
