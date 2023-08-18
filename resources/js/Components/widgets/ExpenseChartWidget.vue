<script lang="ts" setup>
import { computed } from "vue";
import DonutChart from "../organisms/DonutChart.vue";
import exactMath from "exact-math";
import { formatMoney } from "@/utils";

import ExpenseChartWidgetRow from "./ExpenseChartWidgetRow.vue";

const props = defineProps<{
  data: Record<string, number>[];
  componentProps: Record<string, string>;
  title?: string;
  type: 'categories' | 'groups';
}>();

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


</script>

<template>
    <article class="flex items-center justify-center">
        <section class="relative w-[500px]">
            <DonutChart
              style="background: white; width: 100%"
              :series="data"
              :data="data"
              @clicked="handleSelection"
              v-bind="componentProps"
              label="name"
              value="total"
              :legend="false"
            />
            <section class="absolute font-bold text-center top-1/2 left-44 text-primary">
                <h4>
                    {{  title  }}
                </h4>
                <h5>
                    {{ formatMoney(total) }}
                </h5>
            </section>
        </section>
            <div class="space-y-1">
                <ExpenseChartWidgetRow
                    v-for="item in data"
                    :item="item"
                    :type="type"
                    :key="item.id"
                />
            </div>
    </article>

</template>
