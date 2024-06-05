<template>
  <div class="w-full comparison-card">
    <div class="px-5 pb-10">
      <h5 class="p-4 text-left card-title flex flex-col">
        <span>
          {{ title }}
        </span>
        <small>{{ description }}</small>
      </h5>
      <div class="card-text flex">
        <div class="mb-2 flex-col flex justify-center items-center">
          <section class="text-gray-500 space-x-8 bg-gray-100 px-10 comparison-header">
            <div
              class="cursor-pointer comparison-header__item previous-period hover:text-gray-700"
            >
              <h6 class="period-title">Mes</h6>
              <span class="period-value"> {{ formatMoney(headerInfo.month) }}</span>
            </div>
            <div
              class="cursor-pointer comparison-header__item current-period hover:text-gray-700"
            >
              <h6 class="period-title">Año</h6>
              <span class="period-value"> {{ formatMoney(headerInfo.current) }}</span>
            </div>
          </section>
          <div
            class="cursor-pointer justify-center text-lg flex flex-col comparison-header__item current-period hover:text-gray-700"
          >
            <h6 class="period-title text-center">Avg.</h6>
            <span class="period-value text-center">
              {{ formatMoney(headerInfo.avg) }}</span
            >
          </div>
        </div>
        <div style="height: 240px; background: white; width: 100%">
          <apexchart
            ref="apexchart"
            width="100%"
            height="100%"
            type="bar"
            :options="chart.options"
            :series="chart.series"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import formatMoney from "@/utils/formatMoney";

export default {
  props: {
    title: {
      type: String,
      default: "bar",
    },
    description: {
      type: String,
      default: "bar",
    },
    type: {
      type: String,
      default: "bar",
    },
    headerInfo: {
      type: Object,
      required: true,
    },
    chart: {
      type: Object,
      required: true,
    },
    icon: {
      type: String,
      default: "home",
    },
  },
  setup() {
    return {
      formatMoney,
    };
  },
};
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
        background: #60a5fa;
      }
    }
  }
}
</style>
