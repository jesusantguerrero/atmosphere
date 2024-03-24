<script setup lang="ts">
import { computed, reactive } from "vue";

import LogerChart from "@/Components/organisms/LogerChart.vue";

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
            data: monthData.data.map((item: any) => item.total),
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
        interaction: {
            intersect: false,
            mode: 'nearest',
            axis: 'x'
        },
        plugins: {
            legend: {
                display: false,
            },
            title: {
                display: false,
                text: (ctx: any) => {
                    const { axis = 'xy', intersect, mode} = ctx.chart.options.interaction
                    return 'Mode: ' + mode + ', axis: ' + axis + ', intersect: ' + intersect;
                }
            },
        },
        scales: {
            x: {
                ticks: {
                    callback: function(val: any, index: number): string {
                        // @ts-ignore
                        return index % 3 === 0 ? 'Day ' + this.getLabelForValue(val) : ''
                    }
                }
            }
        }
    },
    series: currentSeries
});

</script>

<template>
  <div class="w-full comparison-card">
    <div class="pb-10 rounded-lg">
      <h5 class="py-4 font-bold text-left card-title">
        {{ title }}
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
