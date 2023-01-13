<template>
  <div class="w-full comparison-card">
    <div class="px-5 pb-10 rounded-lg">
      <h5 class="p-4 font-bold text-left card-title">
        <LogerButtonTab v-if="selectedDate" @click="selectedDate=null">
            <i class="fa fa-arrow-left"></i>
        </LogerButtonTab>
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
import { computed, reactive } from "vue";

import LogerChart from "@/Components/organisms/LogerChart.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import { formatMonth, isCurrentMonth } from "@/utils";

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
    return Object.entries(props.data).map(([dateString, monthData]) => {
        return {
            name: formatMonth(dateString),
            data: monthData.data.map(item => item.total),
            labels: Object.keys(monthData).map(month => formatMonth(month)),
            tension: 0,
            fill: false,
            ...(!isCurrentMonth(dateString) && {borderDash: [5, 5]}),
            ...(isCurrentMonth(dateString) && {fill: true }),
        }
    })
})

const state = reactive({
    headers: {
        labels: [...Array(32).keys()].slice(1),
    },
    options: {
        colors: ["#7B77D120", "#F598B420"],
        borderColors: ["#7B77D1", "#F598B4"],
        tension: 0,
        plugins: {
            legend: {
                display: false,
            },
            title: {
                display: false,
            },
        },
        scales: {
            x: {
                ticks: {
                    callback: function(val, index) {
                        return index % 3 === 0 ? 'Day ' + this.getLabelForValue(val) : ''
                    }
                }
            }
        }
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
