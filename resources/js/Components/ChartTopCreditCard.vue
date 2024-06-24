<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";
import { computed, ref, inject } from "vue";

import LogerChart from "./organisms/LogerChart.vue";
import ChartHeaderScroller from "./ChartHeaderScroller.vue";
import NumberHider from "./molecules/NumberHider.vue";
import { formatMonth, nameToColor } from "@/utils";

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
    hideHeaders: {
        type: Boolean
    }
});

const selectedDate = ref()
const currentSeries = computed(() => {
    const groupByCard =  props.data.reduce((creditCards, item) => {
        if (creditCards[item.name]) {
            creditCards[item.name].data.push(item.total),
            creditCards[item.name].labels.push(item.cat_name)
        } else {
            creditCards[item.name] = {
                name: item.name,
                data: [item.total],
                labels: [item.cat_name]
            }
        }
        return creditCards;
    }, {});

    return Object.values(groupByCard);
})

const state = computed(() => ({
    headers: Object.entries(props.data).map(([dateString, item]) => ({
        label:  `${item.cat_name} - ${item.name}`,
        value: [item.total],
        id: item.id
    })),
    options: {
        colors: currentSeries.value.map((series, i) => nameToColor(series.name)),
    },
    series: currentSeries.value
}));

const hasHiddenValues = inject('hasHiddenValues', ref(false))

</script>

<template>
  <div class="w-full comparison-card">
    <div class="pb-10 rounded-lg">
      <div class="card-text" >
        <ChartHeaderScroller v-if="!hideHeaders" item-class="comparison-header__item" class="flex flex-row-reverse">
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
            :type="type"
            :labels="currentSeries[0].labels.map(formatMonth)"
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
