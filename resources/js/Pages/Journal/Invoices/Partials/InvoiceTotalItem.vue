<script lang="ts" setup>
import { computed } from "vue";

const props = defineProps<{
  label: string;
  value: string | number;
  vertical?: boolean;
  valueSize: "sm" | "md" | "lg";
}>();

const valueSizes = {
  md: "text-md",
  sm: "text-sm",
  lg: "text-xl",
};

const valueStyles = computed(() => {
  return valueSizes[props.valueSize] ?? valueSizes.md;
});
</script>

<template>
  <p class="grid px-2 total-labels" :class="[vertical ? 'grid-cols-1' : 'grid-cols-2']">
    <span class="label">{{ label }}:</span>
    <span class="value" :class="valueStyles">{{ value }}</span>
  </p>
</template>

<style lang="scss">
.total-labels {
  color: #909399;
  font-weight: 700;
  margin-bottom: 0.5rem;
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
