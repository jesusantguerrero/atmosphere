<script setup lang="ts">
defineProps<{
  label: string;
  value: string | number;
}>();
</script>

<template>
    <article class="w-full text-center capitalize ">
        <h4 class="font-bold text-secondary">{{ value }}</h4>
        <p class="text-body-1">{{ label }}</p>
    </article>
</template>
