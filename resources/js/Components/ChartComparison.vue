<template>
  <div class="w-full comparison-card">
    <div class="pb-10 px-5">
      <h5 class="card-title text-left p-4">Revenue</h5>
      <div class="card-text">
        <div class="comparison-header px-10 text-gray-500 bg-gray-100 mb-2">
          <div class="comparison-header__item previous-period cursor-pointer hover:text-gray-700">
            <h6 class="period-title">Prevoius Year</h6>
            <span class="period-value"> {{ formatMoney(headerInfo.previous) }}</span>
          </div>
          <div class="comparison-header__item current-period cursor-pointer  hover:text-gray-700">
            <h6 class="period-title">Current Year</h6>
            <span class="period-value"> {{ formatMoney(headerInfo.current) }}</span>
          </div>
        </div>
        <div style="height:240px; background: white; width: 100%">
          <LineChart
            ref="apexchart"
            width="100%"
            height="100%"
            label="name"
            :labels="months"
            :options="chart.options"
            :series="chart.series"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import formatMoney from "@/utils/formatMoney";
import LineChart from "./organisms/LineChart.vue";

const  props = defineProps({
    type: {
      type: String,
      default: "bar"
    },
    headerInfo: {
      type: Object,
      required: true
    },
    chart: {
      type: Object,
      required: true
    },
    icon: {
      type: String,
      default: "home"
    }
  });


  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec' ]
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
      font-size: 24px;
      font-weight: bolder;

      &::before {
        position: absolute;
        left: -20px;
        top: 25%;
        content: "";
        height: 10px;
        width: 10px;
        border-radius: 50%;
        background: #fa6b88;
      }
    }

    &.current-period {
      .period-value::before {
        background: #60A5FA;
      }
    }
  }
}
</style>
