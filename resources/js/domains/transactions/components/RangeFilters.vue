<script setup lang="ts">
import { computed, ref, watch } from "vue";

import { getRangeData } from "@/utils";

const emit = defineEmits(['changed', 'update:startDate', 'update:endDate'])

const props = withDefaults(
  defineProps<{
    ranges: any[];
    startDate: Date;
    endDate: Date;
    method?: string;
    defaultRange?: string;
  }>(),
  {
    ranges: () => [
      {
        label: "1D",
        value: [0, 1],
      },
      {
        label: "7D",
        value: [0, 7],
      },
      {
        label: "30D",
        value: [0, 30],
      },
      {
        label: "90D",
        value: [0, 90],
      },
    ],
    method: "forward",
  }
);

const selectedRange = ref(props.defaultRange ?? "1D");

const isSelected = (label: string) => {
  return selectedRange.value == label;
};

const selectedRangeValue = computed(() => {
  return props.ranges.find((range) => range.label === selectedRange.value).value;
});


watch(selectedRange, () => {
    const [startDate, endDate ] = props.ranges.length
      ? getRangeData(selectedRangeValue.value, props.method)
      : [new Date() , new Date()];
      emit('update:startDate', startDate)
      emit('update:endDate', endDate)
}, { immediate: true })

const onRangeChanged = (rangeLabel: string) => {
  selectedRange.value = rangeLabel;
};
</script>

<template>
    <section class="flex text-xs space-x-2 text-body-1">
        <slot name="beforeRange" />
        <span
          v-for="option in ranges"
          role="button"
          class="rounded-3xl bg-base-lvl-2 py-1 px-4"
          @click="onRangeChanged(option.label)"
          :class="isSelected(option.label) && 'text-primary font-bold bg-primary/10'"
          :title="option.tooltip"
        >
          {{ option.label }}</span
        >
      </section>
</template>
