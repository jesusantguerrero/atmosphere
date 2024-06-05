<script setup lang="ts">
interface Props {
  title: string;
  value: string | number;
}

defineProps<Props>();
</script>

<template>
  <article class="grid grid-rows-section-footer-card">
    <section class="my-auto">
      <h4 class="font-bold text-xs md:text-lg">{{ title }}</h4>
      <h3 class="text-xs md:text-4xl font-bold">
        {{ value }}
      </h3>
    </section>
    <footer class="flex space-x-4 mt-auto">
      <slot name="footer" />
    </footer>
  </article>
</template>
