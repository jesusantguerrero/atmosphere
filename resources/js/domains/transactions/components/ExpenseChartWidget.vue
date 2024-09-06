<script lang="ts" setup>
import { computed } from "vue";
import DonutChart from "@/Components/organisms/DonutChart.vue";
// @ts-ignore
import exactMath from "@/plugins/exactMath"
import { formatMoney } from "@/utils";

import ExpenseChartWidgetRow from "./ExpenseChartWidgetRow.vue";


const cols = {
    1: "grid-cols-1",
    2: "grid-cols-2",
    3: "grid-cols-3"
}

const props = withDefaults(defineProps<{
  data: Record<string, number>[];
  componentProps: Record<string, string>;
  title?: string;
  type: 'categories' | 'groups';
  cols: keyof typeof cols;
}>(), {
    cols: 3
});

const emit = defineEmits(['selected']);

const handleSelection = (item: Record<string, string | number>) => {
    emit('selected', item)
};

const total = computed(() => {
  return props.data.reduce((total: number, item: Record<string, number>) => {
    try {
        return exactMath.add(total, item.total);
    } catch (err) {
        return 0
    }
  }, 0);
});

const widgetDetailsCols = computed(() => cols[props.cols]);


</script>

<template>
    <article class="flex">
        <section class="relative w-[550px] mx-auto flex items-center justify-center">
            <DonutChart
              style="background: white; width: 100%"
              :series="data"
              :data="data"
              @clicked="handleSelection"
              v-bind="componentProps"
              label="name"
              value="total"
              :legend="false"
            >
                <template #total>
                    <section class="font-bold text-center text-primary">
                        <h4>
                            {{  title  }}
                        </h4>
                        <h5>
                            {{ formatMoney(total) }}
                        </h5>
                    </section>
                </template>
            </DonutChart>
        </section>
        <section class="space-y-1 grid" :class="[widgetDetailsCols]">
            <ExpenseChartWidgetRow
                v-for="item in data"
                :item="item"
                :type="type"
                :key="item.id"
            />
        </section>
    </article>

</template>
