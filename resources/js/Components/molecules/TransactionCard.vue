<template>
<div class="transition text-slate-body group" :class="[
    allowSelect && 'cursor-pointer hover:bg-gray-500 border-2 border-transparent hover:border-primary',
    isSelected ? 'odd:bg-primary-100 even:bg-primary' : 'odd:bg-base-lvl-1 even:bg-base-100'

    ]" @click="handleSelect">
  <div class="flex justify-between px-5 py-2">
    <div class="flex space-x-3">
        <div v-if="allowSelect" class="flex items-center h-full"><input type="checkbox" :checked="isSelected" /></div>
        <div class="flex items-center justify-center w-20 px-5 py-3 font-bold text-center transition-all rounded-md bg-base-lvl-3 group-hover:bg-primary group-hover:text-white group-body-1">
            {{ title.slice(0,1) }}
        </div>
        <div>
            <h4 class="font-bold"> {{ title }}</h4>
            <small class="text-sm"> {{ subtitle }}</small>
            <small class="block text-sm"> {{ formatDate(date) }}</small>
        </div>

    </div>
    <div class="flex space-x-5">
        <div class="text-right">
            <h4 class="relative font-bold">
                <NumberHider />
                {{ formatMoney(value, currencyCode)}} <span v-if="expenses" class="text-red-500">({{ formatMoney(expenses, currencyCode) }})</span>
            </h4>
            <small class="text-sm"> {{ status }}</small>
        </div>
        <div v-if="markAsPaid" class="font-bold cursor-pointer text-primary" @click="$emit('paid-clicked')">
            Mark as Paid
        </div>
        <div v-if="markAsApproved" class="font-bold cursor-pointer text-primary" @click="$emit('approved')">
            Approve
        </div>
        <div v-if="allowRemove" class="font-bold cursor-pointer text-primary" @click="$emit('removed')">
            Remove
        </div>
    </div>
    </div>
    <div class="px-5" v-if="expenses">
        <n-progress :percentage="percentage" :stroke-width="1" height="2px" border-radius="0" />
    </div>
</div>
</template>

<script setup>
import { computed } from "vue";
import { NProgress } from "naive-ui";
import formatMoney from "../../utils/formatMoney";
import NumberHider from "./NumberHider.vue";
import { parseISO, format } from "date-fns";

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
    allowRemove: Boolean,
    allowSelect: Boolean,
    isSelected: Boolean,
})

const emit = defineEmits(['selected'])
const percentage = computed(() => {
    const percentage = Number(props.expenses||0) / Number(props.value||0) * 100
    return percentage.toFixed(2);
});

const handleSelect = () => {
    if (props.allowSelect) {
        emit('selected');
    }
}

const formatDate = (dateISOString) => {
    return dateISOString && format(parseISO(dateISOString + 'T00:00:00'), 'MMM dd, yyyy')
}
</script>
