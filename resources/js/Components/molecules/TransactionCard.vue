
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

const handleSelect = () => {
  if (props.allowSelect) {
    emit("selected");
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

const handleOptions = (option: 'remove'|'selected') => {
  emit(option);
};
</script>

<template>
  <div
    class="capitalize transition text-slate-body group"
    :class="[
      allowSelect &&
        'cursor-pointer hover:bg-base-lvl-3 border-2 border-transparent hover:border-primary',
      isSelected
        ? 'odd:bg-primary/20 even:bg-primary/10'
        : 'odd:bg-base-lvl-2 even:bg-base-lvl-1',
    ]"
  >
    <div class="flex justify-between px-5 py-2 space-x-2">
      <div class="flex w-6/12 space-x-3 truncate">
        <div v-if="allowSelect" class="flex items-center h-full">
          <input type="checkbox" :checked="isSelected" @change="handleSelect()" />
        </div>
        <div>
          <h4 class="font-bold truncate">{{ title }}</h4>
          <small class="text-sm"> {{ subtitle }}</small>
        </div>
      </div>
      <div class="flex space-x-5">
        <div class="text-right">
          <h4 class="relative font-bold">
            <NumberHider />
            {{ formatMoney(value, currencyCode) }}
            <span v-if="expenses" class="text-red-500"
              >({{ formatMoney(expenses, currencyCode) }})</span
            >
          </h4>
          <small class="block text-sm" :class="[isDateInPast ? 'text-error font-bold': 'text-secondary']">
            {{ date ? formatDate(date) : valueSubtitle }}
        </small>
        </div>
        <NDropdown
            v-if="hasOptions"
            trigger="click"
            key-field="name"
            :options="options"
            :on-select="handleOptions"
            @click.stop
        >
          <button class="px-2 hover:bg-base-lvl-3">
            <IMdiEllipsisVertical />
          </button>
        </NDropdown>
      </div>
    </div>
    <div class="px-5" v-if="expenses">
      <NProgress
        :percentage="percentage"
        :stroke-width="1"
        height="2px"
        border-radius="0"
      />
    </div>
  </div>
</template>
