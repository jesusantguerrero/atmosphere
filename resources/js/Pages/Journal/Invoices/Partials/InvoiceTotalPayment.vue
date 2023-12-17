/* eslint-disable vue/valid-v-on */
<script lang="ts" setup>
import { formatMoney, formatDate } from "@/utils";

defineProps<{
  payment: {
    amount: number;
    payment_date: string;
  };
}>();

const emit = defineEmits(["edit-payment", "delete-payment"]);
</script>

<template>
  <i
    class="text-green-500 total-labels payment-label group"
    @click.stop="$emit('edit-payment', payment)"
  >
    <p class="flex label">Pagado en {{ formatDate(payment.payment_date) }}</p>
    <p class="flex items-center justify-end text-right value">
      - {{ formatMoney(payment.amount) }}
      <button
        class="hidden text-gray hover:text-success group-hover:flex"
        @click.stop="$emit('edit-payment', payment)"
      >
        <IMdiEdit class="ml-2" />
      </button>
      <button
        class="hidden text-gray hover:text-error group-hover:flex"
        @click.stop="$emit('delete-payment', payment)"
      >
        <IMdiTrash class="ml-2" />
      </button>
    </p>
  </i>
</template>

<style lang="scss" scoped>
.total-labels {
  color: #909399;
  font-weight: 700;
  margin-bottom: 0.5rem;
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-column-gap: 5px;

  &.payment-label {
    cursor: pointer;
    @apply text-green-500;
  }

  .value {
    text-align: right;
    color: #666;
    font-weight: bold;
  }

  .label {
    text-align: right;
    margin-right: 10px;
  }
}

.total-remark {
  font-size: 16px;
  margin: 10px 0;

  .value {
    border-top: 1px solid #666;
  }
}

.invoice-totals {
  margin-top: 1rem;
}
</style>
