<template>
    <span class="relative inline-block">
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
    }
})
</script>
