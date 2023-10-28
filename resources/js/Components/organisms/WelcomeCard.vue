<script setup lang="ts">
import { inject } from "vue";

const {
    message = 'Welcome to Loger'
 } = defineProps<{
    username?: string;
    message: string;
}>();

const cardShadow = inject("cardShadow", '')
</script>

<template>
  <section
    class="px-5 py-3 transition border divide-y rounded-lg divide-base border-base bg-base-lvl-3"
    :class="[cardShadow]"
  >
    <header class="items-center pb-2 md:justify-between md:flex">
        <section class="flex items-center">
            <slot name="before" />

            <h1 class="font-bold text-body">
              {{ message }} <span class="text-primary">{{ username }}</span>
            </h1>
        </section>
      <div class="space-x-2">
        <slot name="action" />
      </div>
    </header>
    <slot></slot>
  </section>
</template>
