<script setup lang="ts">
// @ts-expect-error: no definitions
import { AtButton } from "atmosphere-ui";
import { router } from "@inertiajs/vue3";
import {inject } from "vue";

defineProps({
  username: {
    type: String,
    required: true,
  },
  budget: {
    type: Number,
  },
  expenses: {
    type: [Number, String],
  },
  message: {
    default: "Welcome to Loger",
  },
});

const cardShadow = inject("cardShadow", '')

// @ts-expect-error: error
const goToIndex = () => router.visit(route('budgets.index'))
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
        <AtButton
          class="text-sm text-primary"
          rounded
          @click="goToIndex"
        >
          <i class="fa fa-wallet"></i>
          Edit budget
        </AtButton>
      </div>
    </div>
    <slot></slot>
  </div>
</template>
