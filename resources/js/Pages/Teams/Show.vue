<script setup lang="ts">
    import TeamMemberManager from './TeamMemberManager.vue'
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import DeleteTeamForm from './DeleteTeamForm.vue'
    import JetSectionBorder from '@/Components/atoms/SectionBorder.vue'
    import UpdateTeamNameForm from './UpdateTeamNameForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    defineProps<{
        team: Object,
        availableRoles: {
            key: string;
            description: string;
            name: string;
            permissions: string[]
        }[],
        permissions: Object,
        modules: []
    }>();
</script>


<template>
    <AppLayout title="Settings - Space">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div>
            <div class="pt-16 pb-20 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <UpdateTeamNameForm :team="team" :permissions="permissions" :modules="modules" />

                <TeamMemberManager class="mt-10 sm:mt-0"
                    :team="team"
                    :available-roles="availableRoles"
                    :user-permissions="permissions" />

                <template v-if="permissions?.canDeleteTeam && !team?.personal_team">
                    <JetSectionBorder />

                    <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
