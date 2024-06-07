<script lang="ts" setup>
defineProps<{
  info: {
    value: number;
    currency_code: string;
    title: string;
  };
}>();
</script>

<template>
  <div class="w-4/12 mx-2 p-5 bg-white border-gray-300 border-1 shadow-md rounded-md">
    <h5 class="mb-2">
      {{ info.title }}
    </h5>
    <div class="remark_info text-cool-gray-600 font-extrabold text-xl">
      {{ info.value }}
    </div>

    <div class="links_container mt-3" v-if="info && info.links">
      <a
        class="text-primary text-sm font-bold"
        :href="link.ref"
        v-for="link in info.links"
        :key="link.ref"
      >
        {{ link.label }}
      </a>
    </div>
  </div>
</template>
