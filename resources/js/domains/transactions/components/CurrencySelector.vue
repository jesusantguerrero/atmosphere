<template>
  <div class="currency-selector">
    <NSelect
      v-model:value="selectedCurrency"
      :options="currencyOptions"
      :placeholder="placeholder"
      :disabled="disabled"
      :clearable="clearable"
      filterable
      @update:value="handleCurrencyChange"
      class="w-full"
    >
      <template #option="{ node, option }">
        <div class="flex items-center justify-between w-full">
          <span>{{ option.label }}</span>
          <span class="text-sm text-gray-500">{{ option.symbol }}</span>
        </div>
      </template>
    </NSelect>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { NSelect } from 'naive-ui';
import { getCurrencyOptions, getCurrencyByCode } from '../currency-constants';

interface Props {
  modelValue?: string;
  placeholder?: string;
  disabled?: boolean;
  clearable?: boolean;
  excludeCurrencies?: string[];
}

interface Emits {
  (e: 'update:modelValue', value: string): void;
  (e: 'change', currency: { code: string; name: string; symbol: string } | null): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select currency',
  disabled: false,
  clearable: true,
  excludeCurrencies: () => []
});

const emit = defineEmits<Emits>();

const selectedCurrency = ref(props.modelValue);

const currencyOptions = computed(() => {
  const options = getCurrencyOptions();
  if (props.excludeCurrencies.length > 0) {
    return options.filter(option => !props.excludeCurrencies.includes(option.value));
  }
  return options;
});

const handleCurrencyChange = (value: string) => {
  selectedCurrency.value = value;
  emit('update:modelValue', value);
  
  const currency = value ? getCurrencyByCode(value) : null;
  emit('change', currency);
};

watch(() => props.modelValue, (newValue) => {
  selectedCurrency.value = newValue;
});
</script>

<style scoped>
.currency-selector {
  min-width: 120px;
}
</style>