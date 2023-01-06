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
        <div class="comparison-header w-full md:flex md:px-10 text-body-1/50 md:space-x-2 md:divide-x md:divide-y-0 divide-y divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2 mb-2">
          <div
            v-for="header in state.headers"
            :key="header.id"
            @click="selectedDate = header.id"
            class="comparison-header__item justify-between px-4 md:py-6 py-2 md:justify-center w-full items-center md:flex-col flex  previous-period cursor-pointer hover:text-body/80"
          >
            <h6 class="period-title">{{ header.label }}</h6>
            <span class="period-value text-xs mt-2">
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

<script setup>
import { computed, h, reactive, ref, inject } from "vue";


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

const selectedDate = ref()
const currentSeries = computed(() => {
    const generalSeries = [{
        name: 'Expenses',
        data: Object.values(props.data).map(item => item.total),
        labels: Object.keys(props.data).map(month => formatMonth(month))
    }]
    const dateSeries = selectedDate.value ? [{
        name: formatMonth(selectedDate.value),
        data: props.data[selectedDate.value].data.map(item => item.total),
        labels: props.data[selectedDate.value].data.map(item => item.name)
    }] : []

    return selectedDate.value ? dateSeries : generalSeries;
})

const hasHiddenValues = inject('hasHiddenValues')
const state = reactive({
    headers: Object.entries(props.data).map(([dateString, item]) => ({
        label: formatMonth(dateString),
        value: item.total,
        id: dateString
    })),
    options: {
        colors: ["#7B77D1", "#80CDFE"],
        hasHiddenValues: hasHiddenValues.value
    },
    series: currentSeries
});

</script>

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
