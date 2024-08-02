<script setup lang="ts">
import { computed, ref, inject, watch } from "vue";


import LogerChart from "@/Components/organisms/LogerChart.vue";
import NumberHider from "@/Components/molecules/NumberHider.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import { formatMonth } from "@/utils";
import formatMoney from "@/utils/formatMoney";
import WidgetTitleCard from "../molecules/WidgetTitleCard.vue";

const props = withDefaults(defineProps<{
    title: string;
    hideHeader: boolean;
    headerTemplate: "row" | "grid";
    type: string;
    data: Record<string, any>;
    total: number;
    groupTotal: string;
    dataItemTotal: string;
    dataItemLabel: Function;
    hideDivider: boolean;
    headerLabel: string;
    headerTitleDate: boolean;
    withPadding: boolean;
}>(), {
    headerTemplate: 'row',
    type: "bar",
    groupTotal: "total",
    dataItemTotal: "total",
    headerTitleDate: true,
    withPadding: true
});

const emit = defineEmits(['click', 'subitem-clicked', 'action']);

const selectedDate = ref(null)
const currentSeries = computed(() => {
    const generalSeries = [{
        name: 'Expenses',
        data: Object.values(props.data).map(item => item[props.groupTotal]),
        labels: Object.keys(props.data).map(month => formatMonth(month))
    }]

    const dateSeries = selectedDate.value ? [{
        name: formatMonth(selectedDate.value),
        data: props.data[selectedDate.value].data.map(item => {
            return item[props.dataItemTotal]
        }),
        labels: props.data[selectedDate.value].data.map(item => {
            return props.dataItemLabel ? props.dataItemLabel(item) : item.name
        })
    }] : []

    return selectedDate.value ? dateSeries : generalSeries;
})

const hasHiddenValues = inject('hasHiddenValues', ref(false))
const state = computed(() => {
    return {
        headers: Object.entries(props.data).map(([dateString, item]) => ({
            label: props.headerTitleDate ? formatMonth(dateString) : item[props.headerLabel ?? 'name'],
            value: item[props.groupTotal],
            id: dateString
        })),
        options: {
            colors: ["#7B77D1", "#80CDFE"],
            hasHiddenValues: hasHiddenValues.value,
        },
        series: currentSeries.value
    }
});

watch(() => props.data, () => {
    selectedDate.value = null
})

const handleSelection = (index: number) => {
    if (selectedDate.value) {
       const item = props.data[selectedDate.value].data[index];
       emit('subitem-clicked', item, selectedDate.value)
    }
}

</script>

<template>
    <WidgetTitleCard
        :title="title"
        :action="action"
        :hide-divider="hideDivider"
        @action="$emit('action', $event)"
        :border="false"
        :with-padding="withPadding"
    >
        <template #icon v-if="selectedDate">
            <LogerButtonTab  @click="selectedDate=null">
                <IMdiChevronLeft />
            </LogerButtonTab>
        </template>
        <template #title>
            <span>{{ title }}</span>
            <span class="text-green-500 font-bold ml-4"> {{ formatMoney(total)}}</span>
        </template>
        <template #action v-if="selectedDate">
            <span v-if="selectedDate" class="capitalize text-primary">{{ formatMonth(selectedDate) }}</span>
        </template>

        <section class="w-full card-text" >
            <div
                :class="[headerTemplate == 'grid' ? 'md:grid md:grid-cols-4' : 'md:flex md:divide-x']"
                class="w-full mb-2 divide-y comparison-header md:px-10 text-body-1/50 md:space-x-2  md:divide-y-0 divide-dashed divide-opacity-20 divide-body-1 bg-base-lvl-2">
            <div
                v-for="header in state.headers"
                :key="header.id"
                @click="selectedDate = header.id"
                class="flex items-center  justify-between w-full px-4 py-2 cursor-pointer comparison-header__item md:py-6 md:justify-center md:flex-col previous-period hover:text-body/80"
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
                @clicked="handleSelection"
            />
        </section>
    </WidgetTitleCard>
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
