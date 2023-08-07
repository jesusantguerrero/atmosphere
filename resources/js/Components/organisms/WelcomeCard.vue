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
  <div
    class="px-5 py-3 transition border divide-y rounded-lg divide-base border-base bg-base-lvl-3"
    :class="[cardShadow]"
  >
    <div class="items-center pb-2 md:justify-between md:flex">
      <h1 class="font-bold text-body">
        {{ message }} <span class="text-primary">{{ username }}</span>
      </h1>
      <div class="space-x-2">
        <slot name="action" />
      </div>
    </div>
    <slot></slot>
  </div>
</template>
