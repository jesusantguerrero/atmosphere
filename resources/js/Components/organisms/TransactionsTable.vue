
<template>
<div :class="classes">
    <section-title type="secondary">{{ tableLabel }}</section-title>
    <div :class="tableClass">
        <transaction-card v-bind="transaction" :mark-as-paid="allowMarkAsPaid" :key="transaction.id" v-for="transaction in transactions" @paid-clicked="$emit('paid-clicked', transaction)"/>
    </div>
</div>
</template>

<script>
import TransactionCard from "../molecules/TransactionCard";
import SectionTitle from "../atoms/SectionTitle";
import { computed } from '@vue/runtime-core';

export default {
  components: { SectionTitle, TransactionCard, },
    props: {
        classes: {
            type: String,
            default: 'px-5 mt-5'
        },
        tableClass: {
            type: String,
            default: 'mt-5 bg-white border rounded-lg shadow-md'
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
        }
    },
    setup(props) {

        return {
            transactions: computed(() => {
                return !props.parser ? props.transactions : props.parser(props.transactions);
            })
        }
    }
}
</script>
