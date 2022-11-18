<template>
  <div class="w-full comparison-card">
    <div class="pb-10 px-5 rounded-lg">
      <h5 class="card-title text-left p-4 font-bold">
        <LogerTabButton v-if="selectedDate" @click="selectedDate=null">
            <i class="fa fa-arrow-left"></i>
        </LogerTabButton>
        {{ title }}
        <span v-if="selectedDate" class="capitalize text-primary">{{ formatMonth(selectedDate) }}</span>
      </h5>
      <div class="card-text">
        <LogerChart
            style="height:300px; background: white; width: 100%"
            label="name"
            type="line"
            :labels="state.headers.labels"
            :options="state.options"
            :series="state.series"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from "vue";

import LogerChart from "@/Components/organisms/LogerChart.vue";
import NumberHider from "@/Components/molecules/NumberHider.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";

import { formatMonth } from "@/utils";
import formatMoney from "@/utils/formatMoney";

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

const currentSeries = computed(() => {
    return Object.entries(props.data).map(([monthName, monthData]) => {
        return {
            name: formatMonth(monthName),
            data: monthData.data.map(item => item.total),
            labels: Object.keys(monthData).map(month => formatMonth(month))
        }
    })
})

const state = reactive({
    headers: {
        labels: [...Array(32).keys()].slice(1),
    },
    options: {
        colors: ["#7B77D120", "#80CDFE20"],
        borderColors: ["#7B77D1", "#80CDFE"],
        tension: 0.1
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
  height: 90px;

  &__item {
    width: 50%;
    padding: 20px 0;

    .period-value {
      position: relative;
      font-size: 18px;
      font-weight: bolder;

      &::before {
        position: absolute;
        left: -20px;
        top: 25%;
        content: "";
        height: 10px;
        width: 10px;
        border-radius: 50%;
        background: #8a00d4;
      }
    }
  }
}
</style>
