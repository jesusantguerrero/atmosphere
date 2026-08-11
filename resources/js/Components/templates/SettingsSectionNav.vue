<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const teamId = computed(() => usePage().props.auth.user.current_team_id);

// Section tabs for the (per-user / per-team) Settings hub. Mail driver
// (SMTP/Mailgun/SES) is a system-wide admin concern and lives under
// /admin/mail — deliberately NOT included here.
const sections = computed(() => [
    {
        label: 'Account',
        url: '/user/profile'
    },
    {
        label: 'Security',
        url: '/user/security'
    },
    {
        label: 'Preferences',
        url: '/user/preferences'
    },
    {
        label: 'Budget',
        url: `/teams/${teamId.value}`
    },
    {
        label: 'Integrations',
        url: '/integrations'
    },
    {
        label: 'Messaging',
        url: '/settings/integrations/social'
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
