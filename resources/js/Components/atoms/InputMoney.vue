<script setup lang="ts">
import { useCalculatorInput } from '@/utils/calculator';
// @ts-expect-error: no definitions
import { AtInput } from 'atmosphere-ui';
import { watch } from 'vue';
import { ref, toRefs, nextTick } from 'vue';

const props = defineProps<{
    modelValue: string;
    history?: string|number[];
    disabled?: boolean
}>()

const emit = defineEmits(['update:modelValue', 'update:history']);

const input = ref()
const focus = () => {
    input.value.focus()
}

const { modelValue } = toRefs(props)

const { calculate, history } = useCalculatorInput();

const onBlur = (evt: InputEvent) => {
    const result = parseFloat(calculate(evt.target.value))
    if (result !== 'Error') {
        emit('update:modelValue', result)
        input.value.value = result;
    }
}

watch(() => [...history.value], (historyValue: string[]) => {
    emit('update:history', historyValue.join(''))
})

const emitValue = (value: string) => {
    if (value !== undefined) {
        emit('update:modelValue', value)
    }
}

const onFocus = (evt: InputEvent) => {
    // Select-all when the field is empty OR numerically zero. Was only checking
    // `!modelValue.value`, but "0"/"0.00" are truthy strings, so typing into a
    // zero field prepended the digits ("0" + "3500" = "03500"). Selecting all on
    // focus makes the first keystroke replace the zero instead.
    const raw = String(modelValue.value ?? "").replace(/[^0-9.-]/g, "");
    if (!modelValue.value || Number(raw) === 0) {
        nextTick(() => {
            // @ts-ignore
            evt.target?.setSelectionRange(0, 999);
        })
    }
}

defineExpose({
    focus
})
</script>


<template>
    <AtInput
        type="text"
        class="items-center px-2 rounded-sm bg-base-lvl-2/80 text-body border-base hover:ring-primary"
        :class="{'border-none': disabled}"
        :disabled="disabled"
        v-bind="$attrs"
        ref="input"
        :model-value="modelValue"
        @update:model-value="emitValue"
        @blur="onBlur"
        @focus="onFocus"
    >
    <template v-slot:prefix v-if="$slots.prefix">
        <slot name="prefix" />
    </template>
    <template v-slot:suffix v-if="$slots.suffix">
        <slot name="suffix" />
    </template>
</AtInput>
</template>
