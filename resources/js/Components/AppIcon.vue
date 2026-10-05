<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(defineProps<{
  size?: "small" | "medium" | "large" | "huge";
  theme?: "auto" | "light" | "dark";
}>(), {
  size: "large",
  theme: "auto",
});

const sizes = { small: "28px", medium: "32px", large: "40px", huge: "224px" };
const logoStyle = computed(() => props.size === "huge"
  ? { width: sizes.huge }
  : { height: sizes[props.size] });
</script>

<template>
  <div class="flex flex-col items-center gap-3">
    <img
      v-if="theme !== 'dark'"
      src="/logo.svg"
      alt="Loger"
      width="224"
      height="56"
      :style="logoStyle"
      class="h-auto w-auto max-w-full"
      :class="{ 'dark:hidden': theme === 'auto' }"
    />
    <img
      v-if="theme !== 'light'"
      src="/logo-dark.svg"
      alt="Loger"
      width="224"
      height="56"
      :style="logoStyle"
      class="h-auto w-auto max-w-full"
      :class="{ 'hidden dark:block': theme === 'auto' }"
    />
    <small
      v-if="size === 'huge'"
      class="font-sans text-sm leading-normal"
      :class="theme === 'dark' ? 'text-white/80' : 'text-body-1'"
    >{{ $t('The Family Operating System') }}</small>
  </div>
</template>
