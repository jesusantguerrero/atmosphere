<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const teamId = computed(() => usePage().props.auth.user.current_team_id);

const sections = computed(() => [
    {
        label: 'Profile',
        url: '/user/profile'
    },
    {
        label: 'Budget',
        url: `/teams/${teamId.value}`
    },
    {
        label: 'Emergency Fund Builder',
        url: `/budget-funds/`
    },
    {
        label: 'Integrations',
        url: '/integrations'
    },
])
</script>


<template>
<SectionNav :sections="sections">
    <template #actions>
        <slot name="actions"> <div class="w-1 py-4"></div> </slot>
    </template>
</SectionNav>
</template>
