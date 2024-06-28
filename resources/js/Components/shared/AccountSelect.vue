<script lang="ts" setup>
import { formatMoney } from "@/utils";
import BaseSelect from "./BaseSelect.vue";

const props = defineProps<{
  modelValue: string | Object;
}>();

defineEmits(["update:modelValue"]);
</script>

<template>
  <BaseSelect
    :endpoint="`/loan-accounts`"
    :modelValue="modelValue"
    @update:modelValue="$emit('update:modelValue', $event)"
    placeholder="Selecciona una cuenta"
    label="alias"
    track-by="id"
  >
    <template v-slot:singleLabel="{ option }">
      <span class="option__title">{{ option.alias ?? option.name }}</span>
      <span class="option__small ml-2">({{ formatMoney(option.balance) }}) </span>
    </template>
    <template v-slot:option="{ option }">
      <div class="option__desc">
        <span class="option__title">{{ option.alias ?? option.name }}</span>
        <span class="option__small ml-2">({{ formatMoney(option.balance) }}) </span>
      </div>
    </template>
  </BaseSelect>
</template>
