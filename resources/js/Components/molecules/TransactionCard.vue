
<script setup lang="ts">
import { computed } from "vue";
import { NProgress, NDropdown } from "naive-ui";
import NumberHider from "./NumberHider.vue";

import { formatDate, formatMoney } from "@/utils";
import { isBefore, parseISO } from "date-fns";

const props = defineProps<{
  title: string,
  subtitle: string,
  date: string,
  value: string,
  valueSubtitle: string,
  currencyCode: string,
  status: string,
  markAsPaid: boolean,
  markAsApproved: boolean,
  expenses: number,
  allowEdit: boolean,
  allowRemove: boolean,
  allowSelect: boolean,
  allowMatch: boolean,
  isSelected: boolean,
}>();

const emit = defineEmits([
    "selected",
    "remove",
    "edit",
    "markAsPaid",
    "approve"
]);
const percentage = computed(() => {
  const percentage = (Number(props.expenses || 0) / Number(props.value || 0)) * 100;
  return percentage.toFixed(2);
});

const handleSelect = (domEvent: MouseEvent) => {
  if (props.allowSelect) {
    emit("selected", domEvent);
  }
};

const options = computed(() => {
  const defaultOptions = [
    {
      name: "edit",
      label: "Edit",
      hide: !props.allowEdit
    },
    {
      name: "markAsPaid",
      label: "Mark as paid",
      hide: !props.markAsPaid,
    },
    {
      name: "approved",
      label: "Approve",
      hide: !props.markAsApproved,
    },
    {
      name: "removed",
      label: "Remove",
      hide: !props.allowRemove
    },
    {
      name: "findLinked",
      label: "Find Linked",
      hide: !props.allowMatch,
    },

  ];

  return defaultOptions.filter(option => !option.hide)
});

const hasOptions = computed(() => {
    return Object.keys(options.value).length
})


const isDateInPast = computed(() => {
    return props.date ? isBefore(parseISO(props.date), new Date()) : false
})

const hasMeta = computed(() => {
    return Boolean(props.date || props.subtitle || props.valueSubtitle);
})

const handleOptions = (option: 'remove'|'selected') => {
  emit(option);
};
</script>

<template>
  <div
    class="capitalize transition text-body-1 group"
    :class="[
      allowSelect &&
        'cursor-pointer hover:bg-base-lvl-3 border-2 border-transparent hover:border-primary',
      isSelected
        ? 'odd:bg-primary/20 even:bg-primary/10'
        : 'odd:bg-base-lvl-2 even:bg-base-lvl-1',
    ]"
  >
    <div class="flex items-start gap-2 px-3 py-2.5 sm:px-5 sm:gap-3">
      <div v-if="allowSelect" class="flex items-center pt-0.5 shrink-0">
        <input type="checkbox" :checked="isSelected" @click="handleSelect" />
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <h4 class="text-sm font-bold leading-snug break-words sm:text-base text-body flex-1 min-w-0">
            {{ title }}
          </h4>
          <div class="text-right shrink-0 tabular-nums">
            <h4 class="relative text-sm font-bold leading-snug sm:text-base">
              <NumberHider />
              {{ formatMoney(value, currencyCode) }}
            </h4>
            <span v-if="expenses" class="block text-xs text-red-500">
              ({{ formatMoney(expenses, currencyCode) }})
            </span>
          </div>
        </div>
        <div
          v-if="hasMeta"
          class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-body-1/70 normal-case"
        >
          <span
            v-if="date"
            class="whitespace-nowrap"
            :class="[isDateInPast ? 'text-error font-semibold' : 'text-secondary']"
          >
            {{ formatDate(date) }}
          </span>
          <span v-if="date && (subtitle || valueSubtitle)" class="text-body-1/30">·</span>
          <span v-if="!date && valueSubtitle" class="whitespace-nowrap text-secondary">
            {{ valueSubtitle }}
          </span>
          <template v-else-if="valueSubtitle">
            <span class="truncate text-body-1/80">{{ valueSubtitle }}</span>
            <span v-if="subtitle" class="text-body-1/30">·</span>
          </template>
          <span v-if="subtitle" class="min-w-0 truncate">{{ subtitle }}</span>
        </div>
      </div>
      <NDropdown
          v-if="hasOptions"
          trigger="click"
          key-field="name"
          :options="options"
          :on-select="handleOptions"
          @click.stop
      >
        <button class="shrink-0 px-1.5 py-1 -mr-1 rounded text-body-1/60 hover:text-body hover:bg-base-lvl-3">
          <IMdiEllipsisVertical />
        </button>
      </NDropdown>
    </div>
    <div class="px-5 pb-1" v-if="expenses">
      <NProgress
        :percentage="percentage"
        :stroke-width="1"
        height="2px"
        border-radius="0"
      />
    </div>
  </div>
</template>
