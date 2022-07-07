<template>
  <button
    type="button"
    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-200 transition border border-transparent rounded-md hover:bg-slate-500 hover:text-gray-100 focus:outline-none"
    :class="tabClasses"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  keepActiveState: {
    type: Boolean,
    default: true,
  },
});
const tabClasses = computed(() => {
  const activeStateClass = props.keepActiveState && "focus:bg-slate-600";

  return [activeStateClass];
});
</script>
