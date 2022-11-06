<template>
<SectionNav :sections="menu">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>

<script setup>
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { getSectionMenu, MODULES } from "@/domains/app";
import { computed } from "vue";

const menu = computed(() => {
    return getSectionMenu(MODULES.FINANCE)
})

</script>
