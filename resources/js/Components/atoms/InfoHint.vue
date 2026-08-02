<script setup lang="ts">
import { NTooltip } from "naive-ui";

defineProps<{
  title?: string;
  body?: string;
}>();
</script>

<template>
  <NTooltip trigger="hover" placement="top" :style="{ maxWidth: '260px' }">
    <template #trigger>
      <button
        type="button"
        :aria-label="$t('More info')"
        class="inline-flex items-center align-middle ml-1 text-body-1/40 hover:text-primary transition"
        @click.prevent.stop
      >
        <i class="text-xs fa fa-info-circle" />
      </button>
    </template>
    <div class="text-xs leading-relaxed">
      <div v-if="title" class="mb-0.5 font-semibold">{{ title }}</div>
      <slot>{{ body }}</slot>
    </div>
  </NTooltip>
</template>
