<template>
    <AtInput
        type="text"
        class="bg-base-lvl-2/80 text-base-200 border-base hover:ring-primary rounded-sm"
        v-bind="$attrs"
        required
        ref="input"
        :model-value="modelValue"
        @update:model-value="$emit('update:model-value', $event)"
        @blur="onBlur"
        @focus="onFocus"
    >
    <template v-slot:prefix v-if="$slots.prefix">
        <slot name="prefix" />
    </template>
</AtInput>
</template>

<script setup>
import { useCalculatorInput } from '@/utils/calculator';
import { AtInput } from 'atmosphere-ui';
import { ref, toRefs, nextTick } from 'vue';

const props = defineProps({
   modelValue: {
       type: String,
   },
})

const emit = defineEmits(['update:modelValue']);

const input = ref()
const focus = () => {
    input.value.focus()
}

const { modelValue } = toRefs(props)

const { calculate } = useCalculatorInput();

const onBlur = (evt) => {
    const result = calculate(evt.target.value)
    if (result !== 'Error') {
        emit('update:modelValue', result)
    }
}

const onFocus = (evt) => {
    if (!modelValue.value) {
        nextTick(() => {
            evt.target.setSelectionRange(0, 999);
        })
    }
}

defineExpose({
    focus
})
</script>
