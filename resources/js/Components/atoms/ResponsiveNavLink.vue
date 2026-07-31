<template>
  <div>
    <button :class="classes" class="w-full text-left" v-if="as == 'button'">
      <slot></slot>
    </button>

    <Link :href="href" :class="classes" v-else>
      <slot></slot>
    </Link>
  </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3"
import { computed } from 'vue';

const props = defineProps(["active", "href", "as"]);

const classes = computed(() => {
  return props.active
    ? "block pl-3 pr-4 py-2 border-l-4 border-indigo-400 text-base font-medium text-indigo-700 bg-indigo-50 focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition"
    : "block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-body-1 hover:text-body hover:bg-base-lvl-2 hover:border-base-lvl-2 focus:outline-none focus:text-body focus:bg-base-lvl-2 focus:border-base-lvl-2 transition";
});
</script>
