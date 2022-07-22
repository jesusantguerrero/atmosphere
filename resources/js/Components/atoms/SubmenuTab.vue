<template>
  <button
    type="button"
    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-200 transition border-b-2 border-transparent hover:bg-base-lvl-2 hover:text-gray-100 focus:outline-none"
    :class="[...tabClasses, isSelected && 'border-b-2 border-primary-500 text-primary-500']"
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
  },
  isSelected:{
    type:Boolean,
    default: false
  }
});
const tabClasses = computed(() => {
  const activeStateClass = props.keepActiveState && "focus:bg-base-lvl-1";

  return [activeStateClass];
});
</script>
