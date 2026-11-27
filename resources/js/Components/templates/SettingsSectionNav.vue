<script setup lang="ts">
import SectionNav from "@/Components/molecules/SectionNav.vue";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const teamId = computed(() => usePage().props.auth.user.current_team_id);

// A guest only reaches their own account pages; the household tabs would 403.
const isGuest = computed(() => {
    const user = usePage().props.auth.user;
    return user.all_teams?.find((team: any) => team.id === user.current_team_id)?.membership?.role === 'guest';
});

// Section tabs for the (per-user / per-team) Settings hub. Mail driver
// (SMTP/Mailgun/SES) is a system-wide admin concern and lives under
// /admin/mail — deliberately NOT included here.
const sections = computed(() => isGuest.value ? accountSections : [
    ...accountSections,
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

const accountSections = [
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
]
</script>


<template>
<SectionNav :sections="sections">
    <template #actions>
        <slot name="actions"> <div class="w-1 py-4"></div> </slot>
    </template>
</SectionNav>
</template>
