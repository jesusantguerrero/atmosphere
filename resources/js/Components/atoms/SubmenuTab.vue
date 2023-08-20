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


<template>
  <button
    type="button"
    class="items-center justify-center px-3 py-2 text-sm font-medium leading-4 text-center transition border-b-2 border-transparent after:inline-flex text-body hover:bg-base-lvl-2 hover:text-body/80 focus:outline-none"
    :class="[...tabClasses, isSelected && 'border-b-2 border-primary text-primary']"
    v-bind="$attrs"
  >
    <slot name="icon">
        <i class="mr-2 fa" :class="icon" v-if="icon" />
    </slot>
    <slot />
  </button>
</template>