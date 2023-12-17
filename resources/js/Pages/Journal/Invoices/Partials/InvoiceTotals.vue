/* eslint-disable vue/valid-v-on */
<script lang="ts" setup>
import { formatMoney, formatDate } from "@/utils";
import { computed, reactive, watch, toRefs } from "vue";
import ExactMath from "exact-math";
import InvoiceTotalItem from "./InvoiceTotalItem.vue";

const props = defineProps({
  tableData: {
    type: Array,
    required: true,
  },
  subtotalField: {
    type: String,
    required: true,
  },
  discountField: {
    type: String,
    required: true,
  },
  payments: {
    type: Array,
  },
  totalField: {
    type: String,
    required: true,
  },
  totalValues: {
    type: Object,
    required: true,
  },
  isTaxIncluded: {
    type: Boolean,
    default: false,
  },
  hidePayments: {
    type: Boolean,
    default: false,
  },
  hideDebt: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["edit-payment", "delete-payment"]);

const getRowTaxes = (row: Record<string, string>, taxFields: string[]) => {
  const taxes = {};
  taxFields.forEach((taxField) => {
    taxes[taxField] = row[taxField];
  });
  return taxes;
};

const calculateTaxableAmount = (amount, taxRate) => {
  return ExactMath.formula(`(${amount} / ((100 + ${taxRate}) / 100))`);
};

const calculateRowTaxes = (
  rowTotal: number,
  taxes: Record<string, any>,
  total: Record<string, any>
) => {
  let taxesRowTotal = 0;
  let rowSubtotal = 0;
  taxes.forEach((tax: Record<string, any>) => {
    const taxLabel = `${tax.label || tax.name} ${tax?.rate}%`;
    if (taxLabel && tax.rate) {
      const taxableTotal = props.isTaxIncluded
        ? calculateTaxableAmount(rowTotal, tax.rate)
        : rowTotal;
      const rowTax =
        tax.rate > 100
          ? tax.rate * tax.type
          : ExactMath.formula(`${taxableTotal} * (${tax.rate} / ${100})`) *
            (tax.type ?? 1);
      total.taxes[taxLabel] = (total.taxes[taxLabel] || 0) + (rowTax || 0);
      total.taxesTotal += rowTax;
      taxesRowTotal += rowTax;
      rowSubtotal += taxableTotal;
    }
  });

  return {
    taxesRowTotal,
    rowSubtotal,
  };
};

const state = reactive({
  totals: computed(() => {
    const totals = props.tableData.reduce(
      (total: Record<string, any>, row: Record<string, any>) => {
        const rowTotal = row[props.totalField];
        const { rowSubtotal, taxesRowTotal } = calculateRowTaxes(
          rowTotal,
          row.taxes ?? [],
          total
        );

        const subtotal = rowSubtotal || rowTotal;
        total.subtotal += subtotal;
        total.discountTotal += Number(row[props.discountField]);
        total.total += ExactMath.add(subtotal ?? 0, taxesRowTotal ?? 0);
        total.taxesTotal += taxesRowTotal;

        return total;
      },
      {
        total: 0,
        discountTotal: 0,
        subtotal: 0,
        taxesTotal: 0,
        taxes: {},
      }
    );

    return totals;
  }),
  hasTaxes: computed(() => {
    return Object.keys(state.totals.taxes).length > 0;
  }),

  paymentTotal: computed(() => {
    if (props.payments && props.payments.length) {
      return props.payments.reduce((total, payment) => {
        return (total += parseFloat(payment.amount));
      }, 0);
    }
    return 0;
  }),

  debt: computed(() => {
    return state.totals.total - state.paymentTotal;
  }),
});

watch(
  () => state.totals,
  () => {
    if (state.totals && Object.keys(state.totals).length) {
      Object.keys(state.totals).forEach((key) => {
        props.totalValues[key] = state.totals[key];
      });
    }
  },
  {
    deep: true,
    immediate: true,
  }
);

const { totals, debt, hasTaxes } = toRefs(state);
</script>

<template>
  <div class="invoice-totals">
    <InvoiceTotalItem :label="$t('subtotal')" :value="formatMoney(totals.subtotal)" />
    <template v-if="hasTaxes">
      <InvoiceTotalItem
        v-for="(tax, taxName) in totals.taxes"
        :label="taxName"
        :value="formatMoney(tax)"
      />
    </template>

    <slot name="discount">
      <InvoiceTotalItem :label="$t('discount')" :value="formatMoney(totals.discount)" />
    </slot>
    <slot name="total" :label="$t('total')" :value="formatMoney(totals.total)">
      <InvoiceTotalItem :label="$t('total')" :value="formatMoney(totals.total)" />
    </slot>

    <div v-if="payments && payments.length && !hidePayments">
      <InvoiceTotalPayment
        class="text-green-500 total-labels payment-label group"
        v-for="payment in payments"
        :key="payment.id"
        :payment="payment"
        @click.stop="$emit('edit-payment', payment)"
      />
    </div>

    <slot name="debt" v-if="!hideDebt">
      <InvoiceTotalItem
        class="total-remark"
        :label="$t('debt')"
        :value="formatMoney(debt)"
      />
    </slot>

    <slot name="add-payment" v-if="debt" :slot-scope:scope="{ debt }"> </slot>
  </div>
</template>
