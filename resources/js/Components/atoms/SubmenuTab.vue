<template>
  <button
    type="button"
    class="text-center justify-center after:inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-body transition border-b-2 border-transparent hover:bg-base-lvl-2 hover:text-body/80 focus:outline-none"
    :class="[...tabClasses, isSelected && 'border-b-2 border-primary text-primary']"
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
