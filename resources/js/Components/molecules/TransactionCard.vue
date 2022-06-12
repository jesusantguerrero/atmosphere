<template>
<div class="text-gray-100 transition group" :class="[
    allowSelect && 'cursor-pointer hover:bg-gray-500 border-2 border-transparent hover:border-pink-400',
    isSelected ? 'odd:bg-pink-100 even:bg-pink-200' : 'odd:bg-slate-600 even:bg-slate-100'

    ]" @click="handleSelect">
  <div class="flex justify-between px-5 py-2">
    <div class="flex space-x-3">
        <div v-if="allowSelect" class="flex items-center h-full"><input type="checkbox" :checked="isSelected" /></div>
        <div class="flex items-center justify-center w-20 px-5 py-3 font-bold text-center transition-all rounded-md bg-slate-400 group-hover:bg-pink-400 group-hover:text-white">
            {{ title.slice(0,1) }}
        </div>
        <div>
            <h4 class="font-bold"> {{ title }}</h4>
            <small class="text-sm"> {{ subtitle }}</small>
            <small class="block text-sm"> {{ date }}</small>
        </div>

    </div>
    <div class="flex space-x-5">
        <div class="text-right">
            <h4 class="relative font-bold">
                <div class="absolute w-full h-full text-xl rounded-sm bg-slate-400" v-if="hasHiddenValues" />
                {{ formatMoney(value, currencyCode)}} <span v-if="expenses" class="text-red-500">({{ formatMoney(expenses, currencyCode) }})</span>
            </h4>
            <small class="text-sm"> {{ status }}</small>
        </div>
        <div v-if="markAsPaid" class="font-bold text-pink-500 cursor-pointer" @click="$emit('paid-clicked')">
            Mark as Paid
        </div>
        <div v-if="markAsApproved" class="font-bold text-pink-500 cursor-pointer" @click="$emit('approved')">
            Approve
        </div>
        <div v-if="allowRemove" class="font-bold text-pink-500 cursor-pointer" @click="$emit('removed')">
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
import { computed, inject } from "vue";
import { NProgress } from "naive-ui";
import formatMoney from "../../utils/formatMoney";

const props = defineProps({
    title: String,
    subtitle: String,
    date: String,
    value: String,
    currencyCode: String,
    currencyCode: String,
    status: String,
    markAsPaid: Boolean,
    markAsApproved: Boolean,
    expenses: Number,
    allowRemove: Boolean,
    allowSelect: Boolean,
    isSelected: Boolean,
})

const hasHiddenValues = inject('hasHiddenValues')
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
</script>
