
<template>
<div :class="classes">
    <div class="flex justify-between">
        <SectionTitle type="secondary">{{ tableLabel }}</SectionTitle>
        <div>
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
            @selected="handleSelect(transaction)"
            @paid-clicked="$emit('paid-clicked', transaction)"
            @approved="$emit('approved', transaction)"
            @removed="$emit('removed', transaction)"
        />
    </div>
</div>
</template>

<script setup>
import TransactionCard from "../molecules/TransactionCard";
import SectionTitle from "../atoms/SectionTitle";
import { computed, reactive } from 'vue';

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
        if (isSelected(transaction)) {
            selectedItems.splice(selectedItems.indexOf(transaction.id || transaction.title), 1);
        } else {
            selectedItems.push(transaction.id || transaction.title);
        }
    }
    emit('update:selected', selectedItems);
}
</script>
