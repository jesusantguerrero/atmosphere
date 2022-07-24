<template>
<AtInput type="text" class="bg-base-lvl-2/80 text-base-200 border-base" v-bind="$attrs" rounded required :model-value="modelValue" @update:model-value="$emit('update:model-value', $event)" />
</template>

<script setup>
import { AtInput } from 'atmosphere-ui';
defineProps({
   modelValue: {
       type: String,
   },
})
</script>
