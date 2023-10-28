<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";

defineProps<{
    sections?: any[]
}>();
</script>

<template>
<SectionNav :sections="sections">
    <template #actions>
        <slot name="actions" />
    </template>
</SectionNav>
</template>
