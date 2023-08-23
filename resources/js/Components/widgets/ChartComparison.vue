<script setup lang="ts">
import { computed, reactive, ref, inject } from "vue";


import LogerChart from "@/Components/organisms/LogerChart.vue";
import NumberHider from "@/Components/molecules/NumberHider.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import { formatMonth } from "@/utils";
import formatMoney from "@/utils/formatMoney";

const props = defineProps({
    title: {
        type: String
    },
    hideHeader: {
        type: Boolean
    },
    headerTemplate: {
        type: String,
        default: 'row'
    },
    type: {
      type: String,
      default: "bar"
    },
    data: {
      type: Object,
      required: true
    },
    dataItemTotal: {
        type: String,
        default: "total"
    }
});

const selectedDate = ref()
const currentSeries = computed(() => {
    const generalSeries = [{
        name: 'Expenses',
        data: Object.values(props.data).map(item => item.total),
        labels: Object.keys(props.data).map(month => formatMonth(month))
    }]
    const dateSeries = selectedDate.value ? [{
        name: formatMonth(selectedDate.value),
        data: props.data[selectedDate.value].data.map(item => item[props.dataItemTotal]),
        labels: props.data[selectedDate.value].data.map(item => item.name)
    }] : []

    return selectedDate.value ? dateSeries : generalSeries;
})

const hasHiddenValues = inject('hasHiddenValues', ref(false))
const state = computed(() => {
    return {
        headers: Object.entries(props.data).map(([dateString, item]) => ({
            label: formatMonth(dateString),
            value: item.total,
            id: dateString
        })),
        options: {
            colors: ["#7B77D1", "#80CDFE"],
            hasHiddenValues: hasHiddenValues.value
        },
        series: currentSeries.value
    }
});

</script>

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
      <div class="card-text" >
        <div
            :class="[headerTemplate == 'grid' ? 'md:grid md:grid-cols-4' : 'md:flex']"
            class="w-full mb-2 divide-y comparison-header md:px-10 text-body-1/50 md:space-x-2 md:divide-x md:divide-y-0 divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2">
          <div
            v-for="header in state.headers"
            :key="header.id"
            @click="selectedDate = header.id"
            class="flex items-center justify-between w-full px-4 py-2 cursor-pointer comparison-header__item md:py-6 md:justify-center md:flex-col previous-period hover:text-body/80"
          >
            <h6 class="period-title">{{ header.label }}</h6>
            <span class="mt-2 text-xs period-value">
                <NumberHider />
                {{ formatMoney(header.value) }}
            </span>
          </div>
        </div>
        <LogerChart
            class="bg-white"
            style="height:300px; width: 100%"
            label="name"
            type="bar"
            :labels="currentSeries[0].labels"
            :options="state.options"
            :series="state.series"
            :has-hidden-values="hasHiddenValues"
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
  width: 100%;

  &__item {
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
