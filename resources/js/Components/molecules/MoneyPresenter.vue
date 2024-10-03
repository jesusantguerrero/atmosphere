<script setup lang="ts">
import { formatMoney } from '@/utils';
import NumberHider from './NumberHider.vue';

defineProps<{
    value?: number|string;
    bold?: boolean;
}>()
</script>


<template>
    <span class="relative inline-block" :class="{'font-bold': bold}">
        <NumberHider />
        {{ formatMoney(value) }}
    </span>
</template>
