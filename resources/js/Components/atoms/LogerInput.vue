<script setup lang="ts">
// @ts-expect-error: no definitions
import { AtInput } from 'atmosphere-ui';
import { ref } from 'vue';

defineProps<{
    modelValue: string,
    disabled?: boolean
}>()

const input = ref()
const focus = () => {
    input.value.focus()
}

defineExpose({
    focus
})
</script>


<template>
<AtInput
    type="text"
    class="items-center px-2 rounded-sm bg-base-lvl-2/80 text-base-200 border-base hover:ring-primary"
    :class="{'border-none': disabled}"
    :disabled="disabled"
    v-bind="$attrs"
    required
    ref="input"
    :model-value="modelValue"
    @update:model-value="$emit('update:model-value', $event)"
>
    <template v-slot:prefix v-if="$slots.prefix">
        <slot name="prefix" />
    </template>
    <template v-slot:suffix v-if="$slots.prefix">
        <slot name="suffix" />
    </template>
</AtInput>
</template>
