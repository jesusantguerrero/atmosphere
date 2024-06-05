<script lang="ts" setup>
import { getSectionMenu, MODULES } from "@/domains/app";
import { computed } from "vue";
import SectionNav from "@/Components/molecules/SectionNav.vue";

const menu = computed(() => {
  return getSectionMenu(MODULES.ADMIN);
});
</script>

<template>
  <SectionNav :sections="menu">
    <template #actions>
      <slot name="actions" />
    </template>
  </SectionNav>
</template>
