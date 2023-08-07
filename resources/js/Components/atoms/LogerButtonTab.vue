
<script setup lang="ts">
import { computed } from "vue";

const props = defineProps({
  keepActiveState: {
    type: Boolean,
    default: true,
  },
  icon: {
    type: String
  },
  rounded: {
    type: Boolean,
    default: false,
  }
});
const tabClasses = computed(() => {
  const activeStateClass = props.keepActiveState && "focus:bg-base-lvl-1";
  const shape = props.rounded ? "rounded-full h-8 w-8" : ""
  return [activeStateClass, shape];
});
</script>


<template>
  <button
    type="button"
    class="inline-flex items-center px-2 py-2 text-sm font-medium leading-4 transition border border-transparent rounded-md text-body hover:bg-base-lvl-2 hover:text-body/80 focus:outline-none"
    :class="tabClasses"
    v-bind="$attrs"
  >
    <slot name="icon">
        <i class="mr-2 fa" :class="icon" v-if="icon" />
    </slot>
    <slot />
  </button>
</template>
