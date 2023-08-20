<script setup lang="ts">
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


<template>
    <span class="relative inline-block" :class="{'font-bold': bold}">
        <NumberHider />
        {{ formatMoney(value) }}
    </span>
</template>