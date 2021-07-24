<template>
<div>
  <div class="flex justify-between px-5 py-2">
    <div class="flex space-x-3">
        <div class="w-20 px-5 py-3 font-bold text-center rounded-md bg-gray-50">
            MC
        </div>
        <div>
            <h4 class="font-bold"> {{ title }}</h4>
            <small class="text-sm"> {{ subtitle }}</small>
        </div>

    </div>
    <div class="flex space-x-5">
        <div>
            <h4 class="font-bold"> {{ formatMoney(value)}} <span v-if="expenses" class="text-red-500">({{ formatMoney(expenses) }})</span></h4>
            <small class="text-sm"> {{ status }}</small>
        </div>
        <div v-if="markAsPaid">
            Mark as Paid
        </div>
    </div>
    </div>
    <div class="px-5" v-if="expenses">
        <n-progress :percentage="percentage" :stroke-width="1" height="2px" border-radius="0" />
    </div>
</div>
</template>

<script setup>
import { computed } from "@vue/runtime-core";
import { NProgress } from "naive-ui";
import formatMoney from "../../utils/formatMoney";

const props = defineProps({
    title: String,
    subtitle: String,
    value: String,
    status: String,
    markAsPaid: Boolean,
    expenses: Number
})

const percentage = computed(() => {
    const percentage = Number(props.expenses||0) / Number(props.value||0) * 100
    return percentage.toFixed(2);
});
</script>
