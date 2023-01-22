<template>
  <Link :href="href" :class="classes">
    <slot></slot>
  </Link>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
const props = defineProps({
  href: {
    type: string,
  },
  active: {
    type: Boolean,
  },
});

const classes = computed(() => {
  return props.active
    ? "inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 focus:outline-none focus:border-indigo-700 transition"
    : "inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition";
});
</script>
