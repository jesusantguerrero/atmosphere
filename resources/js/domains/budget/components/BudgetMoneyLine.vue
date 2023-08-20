<script setup lang="ts">
import MoneyPresenter from '@/Components/molecules/MoneyPresenter.vue';

defineProps<{
    title: string;
    value: string | number
}>()
</script>


<template>
    <p class="flex justify-between">
        {{ title }}
        <MoneyPresenter bold :value="Number(value)" />
    </p>
</template>