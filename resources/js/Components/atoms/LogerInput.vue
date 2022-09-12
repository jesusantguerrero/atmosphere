<template>
<AtInput
    type="text"
    class="bg-base-lvl-2/80 text-base-200 border-base hover:ring-primary rounded-sm"
    v-bind="$attrs"
    required
    ref="input"
    :model-value="modelValue"
    @update:model-value="$emit('update:model-value', $event)"
/>
</template>

<script setup>
import { AtInput } from 'atmosphere-ui';
import { ref } from 'vue';

defineProps({
   modelValue: {
       type: String,
   },
})

const input = ref()
const focus = () => {
    input.value.focus()
}

defineExpose({
    focus
})
</script>
