<template>
<SectionNav :sections="getSectionMenu(MODULES.HOUSING)">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>


<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { getSectionMenu, MODULES } from "@/domains/app";

</script>
