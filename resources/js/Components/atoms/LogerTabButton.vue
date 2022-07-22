<template>
  <button
    type="button"
    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-200 transition border border-transparent rounded-md hover:bg-base-lvl-2 hover:text-gray-100 focus:outline-none"
    :class="tabClasses"
    v-bind="$attrs"
  >
    <slot name="icon">
        <i class="fa mr-2" :class="icon" v-if="icon" />
    </slot>
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
  icon: {
    type: String
  }
});
const tabClasses = computed(() => {
  const activeStateClass = props.keepActiveState && "focus:bg-base-lvl-1";

  return [activeStateClass];
});
</script>
