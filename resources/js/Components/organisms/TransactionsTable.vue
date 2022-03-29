
<template>
<div :class="classes">
    <div class="flex justify-between">
        <SectionTitle type="secondary" class="w-full">{{ tableLabel }}</SectionTitle>
        <div class="w-full">
            <slot name="action" />
        </div>
    </div>
    <div :class="tableClass">
        <TransactionCard
            v-for="transaction in transactionsParsed"
            v-bind="transaction"
            :mark-as-paid="allowMarkAsPaid"
            :mark-as-approved="allowMarkAsApproved"
            :allow-remove="allowRemove"
            :allow-select="allowSelect"
            :key="transaction.id"
            :isSelected="isSelected(transaction)"
            class="odd:bg-white even:bg-slate-100"
            @selected="handleSelect(transaction)"
            @paid-clicked="$emit('paid-clicked', transaction)"
            @approved="$emit('approved', transaction)"
            @removed="$emit('removed', transaction)"
        />
        <div class="flex items-center justify-between py-5 bg-gray-200 px-4" v-if="showSum">
            <div class="font-bold">Selected Items sum</div>
            <div class="text-right space-y-1">
                <div v-for="currencySum, currency in selectedSum">
                    {{ formatMoney(currencySum, currency) }}
                </div>
            </div>
        </div>
    </div>
</div>
</template>

<script setup>
import TransactionCard from "../molecules/TransactionCard";
import SectionTitle from "../atoms/SectionTitle";
import { computed, reactive } from 'vue';
import ExactMath from "exact-math";
import formatMoney from "@/utils/formatMoney";

const props = defineProps({
    classes: {
        type: String,
        default: 'mt-1'
    },
    tableClass: {
        type: String,
        default: 'mt-2 bg-white border rounded-lg shadow-md'
    },
    tableLabel: {
        type: String,
        default: ''
    },
    transactions: {
        type: Array,
        default() {
            return []
        }
    },
    parser: {
        type: [Function, null],
        default: null
    },
    showSum: {
        type: Boolean,
        default: false
    },
    allowMarkAsPaid: {
        type: Boolean,
        default: false
    },
    allowMarkAsApproved: {
        type: Boolean,
        default: false
    },
    allowRemove: {
        type: Boolean,
        default: false
    },
    allowSelect: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update:selected']);
const transactionsParsed = computed(() => {
    return !props.parser ? props.transactions : props.parser(props.transactions);
})

const selectedItems = reactive([]);

const isSelected = (transaction) => {
    return selectedItems.includes(transaction.id || transaction.title);
}

const handleSelect = (transaction) => {
    if (props.allowSelect) {
        const id = transaction.id || transaction.title;
        if (isSelected(transaction)) {
            selectedItems.splice(selectedItems.indexOf(id), 1);
        } else {
            selectedItems.push(id);
        }
    }
    emit('update:selected', selectedItems);
}

const selectedSum = computed(() => {
    return calculateSum(selectedItems);
})

const totalSum = computed(() => {
    return calculateSum(transactionsParsed.value.map(transaction => transaction.id));
})

const calculateSum = (items) => {
     return items.reduce((sum, id) => {
        const transaction = transactionsParsed.value.find(transaction => transaction.id === id || transaction.title === id);
        if (transaction) {
            if (sum[transaction.currencyCode]) {
                sum[transaction.currencyCode] = ExactMath.add(sum[transaction.currencyCode],  transaction.value);
            } else {
                sum[transaction.currencyCode] = ExactMath.add(0, transaction.value);
            }
        }
        return sum;
    }, {

    });
}
</script>
