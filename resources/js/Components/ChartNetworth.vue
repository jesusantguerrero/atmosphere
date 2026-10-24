<script setup lang="ts">
import formatMoney from "@/utils/formatMoney";
import { computed, ref, inject } from "vue";
import { useI18n } from "vue-i18n";
import { useDarkMode } from "@/composables/useDarkMode";

import LogerChart from "./organisms/LogerChart.vue";
import ChartHeaderScroller from "./ChartHeaderScroller.vue";
import NumberHider from "./molecules/NumberHider.vue";
import { formatMonth } from "@/utils";

const { t, locale } = useI18n();
const { isDark } = useDarkMode();
const monthLabel = (date: string) => new Intl.DateTimeFormat(locale.value, { month: "short", year: "2-digit" }).format(new Date(`${date}T12:00:00`));

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
    return [{
        name: t('Debts'),
        data: Object.values(props.data).map(item => Math.abs(item.debts)),
        labels: Object.values(props.data).map(item => item.date_unit)
    },
    {
        name: t('Assets'),
        data: Object.values(props.data).map(item => item.assets),
        labels: Object.values(props.data).map(item => item.date_unit)
    }];
})

const tickColor = computed(() => isDark.value ? '#A6AFBD' : '#69727F');
const state = computed(() => ({
    headers: Object.entries(props.data).map(([dateString, item]) => ({
        label: formatMonth(item.date_unit),
        value: [item.debts, item.assets],
        id: item.date_unit
    })),
    options: {
        colors: ["#E8837E33", "#56C08A33"],
        borderColors: ["#E8837E", "#56C08A"],
        borderWidth: 1.5,
        summaryLineColor: "#7B77D1",
        scales: { x: { ticks: { color: tickColor.value, maxRotation: 0 } }, y: { ticks: { color: tickColor.value, callback: (value: number) => hasHiddenValues.value ? "--" : new Intl.NumberFormat(locale.value, { notation: "compact" }).format(value) } } },
        plugins: { legend: { labels: { color: tickColor.value } } },
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
            <span class="relative text-sm period-value" v-for="(value, index) in header.value">
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
            :labels="currentSeries[0].labels.map(monthLabel)"
            :options="state.options"
            :series="state.series"
            :has-hidden-values="hasHiddenValues"
            summary-line
            :summary-line-label="$t('Net worth')"
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
