<template>
    <p class="flex justify-between">
        {{ title }}
        <MoneyPresenter bold :value="value" />
    </p>
</template>

<script setup>
import MoneyPresenter from './MoneyPresenter.vue';

defineProps({
    title: {
        type: String
    },
    value: {
        type: [String, Number]
    }
})
</script>
