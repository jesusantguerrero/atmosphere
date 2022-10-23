<template>
    <span class="relative inline-block" :class="{'font-bold': bold}">
        <NumberHider />
        {{ formatMoney(value) }}
    </span>
</template>

<script setup>
import { formatMoney } from '@/utils';
import NumberHider from './NumberHider.vue';

defineProps({
    value: {
        type: Number
    },
    bold: {
        type: Boolean,
    }
})
</script>
