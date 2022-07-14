<template>
<AtInput type="text" class="bg-slate-500/80 text-slate-200 border-slate-700" v-bind="$attrs" rounded required :model-value="modelValue" @update:model-value="$emit('update:model-value', $event)" />
</template>

<script setup>
import { AtInput } from 'atmosphere-ui';
defineProps({
   modelValue: {
       type: String,
   },
})
</script>
