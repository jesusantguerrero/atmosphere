<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { getSectionMenu, MODULES } from "@/domains/app";
import { computed } from "vue";

/**
 * AdminSectionNav — top-level tabs for /admin/* (Overview, Users,
 * Teams, Feature Flags). Wire matches FinanceSectionNav so the header
 * looks and behaves identically across sections; the menu items
 * themselves come from menus.ts (MODULES.ADMIN entry).
 */

const menu = computed(() => {
    return getSectionMenu(MODULES.ADMIN);
});
</script>

<template>
    <SectionNav :sections="menu">
        <template #actions>
            <slot name="actions" />
        </template>
    </SectionNav>
</template>
