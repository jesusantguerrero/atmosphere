<template>
  <div
    class="transition text-slate-body group"
    :class="[
      allowSelect &&
        'cursor-pointer hover:bg-base-lvl-3 border-2 border-transparent hover:border-primary',
      isSelected
        ? 'odd:bg-primary-100 even:bg-primary'
        : 'odd:bg-base-lvl-2 even:bg-base-lvl-1',
    ]"
  >
    <div class="flex justify-between px-5 py-2">
      <div class="flex space-x-3">
        <div v-if="allowSelect" class="flex items-center h-full">
          <input type="checkbox" :checked="isSelected" @change="handleSelect()" />
        </div>
        <div
          class="md:flex items-center hidden justify-center w-20 px-5 py-3 font-bold text-center transition-all rounded-md bg-base-lvl-3 group-hover:bg-primary group-hover:text-white group-body-1"
        >
          {{ title.slice(0, 1) }}
        </div>
        <div>
          <h4 class="font-bold">{{ title }}</h4>
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
          <small class="block text-sm"> {{ formatDate(date) }}</small>
        </div>
        <NDropdown
            v-if="hasOptions"
            trigger="click"
            key-field="name"
            :options="options"
            :on-select="handleOptions"
            @click.stop
        >
          <button class="hover:bg-base-lvl-3 px-2"> <i class="fa fa-ellipsis-v"></i></button>
        </NDropdown>
      </div>
    </div>
    <div class="px-5" v-if="expenses">
      <n-progress
        :percentage="percentage"
        :stroke-width="1"
        height="2px"
        border-radius="0"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { NProgress, NDropdown } from "naive-ui";
import NumberHider from "./NumberHider.vue";
import LogerTabButton from "../atoms/LogerTabButton.vue";

import { formatDate, formatMoney } from "@/utils";

const props = defineProps({
  title: String,
  subtitle: String,
  date: String,
  value: String,
  currencyCode: String,
  status: String,
  markAsPaid: Boolean,
  markAsApproved: Boolean,
  expenses: Number,
  allowEdit: Boolean,
  allowRemove: Boolean,
  allowSelect: Boolean,
  isSelected: Boolean,
});

const emit = defineEmits(["selected", 'remove']);
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

  ];

  return defaultOptions.filter(option => !option.hide)
});

const hasOptions = computed(() => {
    return Object.keys(options.value).length
})

const handleOptions = (option) => {
  emit(option);
};
</script>
