<script setup lang="ts">
import AppLayout from "@/Components/templates/AppLayout.vue";
import AdminSectionNav from "./AdminSectionNav.vue";

/**
 * AdminTemplate — shared shell for every /admin/* page. Wraps AppLayout,
 * mounts the section nav in the header, and provides a max-width main
 * region with dark-mode-native surfaces. Style matches Loger's Finance
 * pages (coral primary, base-lvl-* surfaces) — deliberately NOT copied
 * from the light-gray patterns other admin scaffolds ship with.
 */

defineProps<{
    title?: string;
    showBackButton?: boolean;
}>();
</script>

<template>
    <AppLayout :title="title" :show-back-button="showBackButton">
        <template #header>
            <AdminSectionNav />
        </template>

        <main class="px-3 mx-auto mt-16 mb-10 max-w-screen-2xl sm:px-6 lg:px-8">
            <slot />
        </main>
    </AppLayout>
</template>
