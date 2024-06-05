<script setup lang="ts">
import AppLayout from "@/Components/templates/AppLayout.vue";
import AdminSectionNav from "./AdminSectionNav.vue";

defineProps<{
  user?: Record<string, string>;
}>();
</script>

<template>
  <AppLayout title="Dashboard">
    <template #header>
      <AdminSectionNav />
    </template>

    <main class="p-5 mt-8 pt-0 mx-auto text-gray-500 sm:px-6 lg:px-8">
      <div class="flex justify-between mt-4 md:mt-0 mb-4" v-if="user">
        <h4 class="hidden md:inline-block">{{ $t("Welcome") }}, {{ user.name }}</h4>
      </div>
      <slot />
    </main>
  </AppLayout>
</template>
