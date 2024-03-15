<script setup lang="ts">
import MoneyPresenter from '../molecules/MoneyPresenter.vue';

defineProps<{
  label: string;
  value: string | number;
  isMoney?: boolean;
}>();
</script>

<template>
    <article class="w-full text-center capitalize ">
        <h4 class="font-bold text-secondary">
            <MoneyPresenter :value="value" class="mr-2" v-if="isMoney"/>
            <span v-else>
                {{ value }}
            </span>
        </h4>
        <p class="text-body-1">{{ label }}</p>
    </article>
</template>
