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
  },
  value: {
    type: String,
  },
  currentValue: {
    type: String,
  },
  label: {
    type: String,
  },
  isActiveFunction: {
    type: Function,
  },
  tabClass: {
    type: String,
    default: "",
  },
  selectedClass: {
    type: String,
    default: "border-primary text-primary",
  },
  as: {
    type: String,
    default: "button",
  },
});

const tabClasses = computed(() => {
  const activeStateClass = props.keepActiveState && "focus:bg-base-lvl-1";

  return [activeStateClass];
});

const isActive = computed(() => {
  const regex = new RegExp(props.value ?? "");
  const currentPath = window.location.pathname;

  if (props.value?.startsWith?.("/")) {
    return props.isActiveFunction
      ? props.isActiveFunction(currentPath)
      : regex.test(currentPath);
  }
  return props.value == props.currentValue;
});
</script>


<template>
<component
    :is="as"
    :type="as"
    :href="value"
    class="items-center justify-center px-3 py-2 text-sm font-medium leading-4 text-center transition border-b-2 border-transparent after:inline-flex text-body hover:bg-base-lvl-2 hover:text-body/80 focus:outline-none"
    :class="[...tabClasses, isSelected && isActive && 'border-b-2 border-primary text-primary']"
    v-bind="$attrs"
  >
    <slot name="icon">
        <i class="mr-2 fa" :class="icon" v-if="icon" />
    </slot>
    <slot />
</component>
</template>
