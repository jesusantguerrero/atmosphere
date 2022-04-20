
<template>
<div :class="classes">
    <div class="flex justify-between">
        <SectionTitle type="secondary" class="w-full">{{ tableLabel }}</SectionTitle>
        <div class="w-full justify-end flex items-center">
            <slot name="action" />
        </div>
    </div>
    <div :class="tableClass">
        <template v-if="transactionsParsed.length">
            <TransactionCard
                v-for="(transaction, index) in transactionsParsed"
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
                @dblclick="$emit('edit', transactions[index])"
            />
            <div class="flex items-center justify-between py-5 bg-gray-200 px-4" v-if="showSum">
                <div class="font-bold">Selected Items sum</div>
                <div class="text-right space-y-1">
                    <div v-for="currencySum, currency in selectedSum">
                    {{ formatMoney(currencySum, currency) }}
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="text-center text-gray-600 py-6 px-5 items-center justify-center">
            <div class="mx-auto text-6xl">
               <svg xmlns="http://www.w3.org/2000/svg"  xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--iconoir mx-auto" width="1em" height="1em" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="M7 2h9.5L21 6.5V19"></path><path d="M3 20.5v-14A1.5 1.5 0 0 1 4.5 5h9.752a.6.6 0 0 1 .424.176l3.148 3.148A.6.6 0 0 1 18 8.75V20.5a1.5 1.5 0 0 1-1.5 1.5h-12A1.5 1.5 0 0 1 3 20.5Z"></path><path fill="currentColor" d="M14 8.4V5.354a.354.354 0 0 1 .604-.25l3.292 3.292a.353.353 0 0 1-.25.604H14.6a.6.6 0 0 1-.6-.6Z"></path></g></svg>
            </div>
            <span class="font-bold text-lg mt-10">
                No transactions found
            </span>
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

const emit = defineEmits(['update:selected', 'edit', 'paid-clicked', 'approved', 'removed']);
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
