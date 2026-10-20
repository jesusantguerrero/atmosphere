<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { getSectionMenu, MODULES } from "@/domains/app";
const page = usePage();
const selectedTab = computed(() =>
    page.url.split(/[?#]/)[0] === "/loger-profiles/overview"
        ? "/loger-profiles/overview"
        : "/loger-profiles",
);
</script>
<template>
    <SectionNav
        :sections="getSectionMenu(MODULES.PROFILES)"
        :model-value="selectedTab"
    >
        <template #actions><slot name="actions" /></template>
    </SectionNav>
</template>
