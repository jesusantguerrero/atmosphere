<script setup lang="ts">
// @ts-ignore
import { AtField, AtInput } from "atmosphere-ui";

withDefaults(
  defineProps<{
    label?: string;
    modelValue?: any;
    required?: boolean;
    placeholder?: string;
    disabled?: boolean;
    numberFormat?: boolean;
  }>(),
  {
    required: false,
  }
);
</script>

<template>
  <AtField
    :label="label"
    class="w-full capitalize text-primary font-bold"
    :required="required"
  >
    <slot>
      <article>
        <AtInput
          :model-value="modelValue"
          @update:modelValue="$emit('update:modelValue', $event)"
          rounded
          :required="required"
          :placeholder="label || placeholder"
          :disabled="disabled"
          :number-format="numberFormat"
          class="bg-neutral/20 shadow-none placeholder:first-letter:capitalize border-neutral hover:border-primary/60 focus:border-primary/60"
        >
          <template #suffix>
            <slot name="suffix" />
          </template>
          <template #prefix>
            <slot name="prefix" />
          </template>
        </AtInput>
      </article>
    </slot>
  </AtField>
</template>

<style lang="scss">
.form-group[required="true"] {
  label::after {
    content: "*";
    @apply text-error/80;
  }
}
</style>
