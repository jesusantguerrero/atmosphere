<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { usePage } from "@inertiajs/vue3";

const teamId = usePage().props.user.current_team_id;
</script>


<template>
<SectionNav :sections="[
    {
        label: 'Profile',
        url: '/user/profile'
    },
    {
        label: 'Api Tokens',
        url: '/user/api-tokens'
    },
    {
        label: 'Budget',
        url: `/teams/${teamId}`
    },
    {
        label: 'Integrations',
        url: '/integrations'
    },
    ]">
    <template #actions>
        <slot name="actions"> <div class="w-1 py-4"></div> </slot>
    </template>
</SectionNav>
</template>
