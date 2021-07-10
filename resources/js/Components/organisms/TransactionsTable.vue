
<template>
<div :class="classes">
    <section-title type="secondary">{{ tableLabel }}</section-title>
    <div :class="tableClasses">
        <transaction-card title="MCTekk" subtitle="Pago de nomina" status="PAID" value="60,000.00" />
        <transaction-card title="MCTekk" subtitle="Pago de nomina" status="PAID" value="60,000.00" />
        <transaction-card title="MCTekk" subtitle="Pago de nomina" status="PAID" value="60,000.00" />
    </div>
</div>
</template>

<script>
import TransactionCard from "../molecules/TransactionCard";
import SectionTitle from "../atoms/SectionTitle";

export default {
  components: { SectionTitle, TransactionCard, },
    props: {
        class: {
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
        }
    },
}
</script>
