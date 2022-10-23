<template>
    <AppLayout title="Settings - Teams">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <UpdateTeamNameForm :team="team" :permissions="permissions" />

                <TeamMemberManager class="mt-10 sm:mt-0"
                            :team="team"
                            :available-roles="availableRoles"
                            :user-permissions="permissions" />

                <template v-if="permissions.canDeleteTeam && ! team.personal_team">
                    <JetSectionBorder />

                    <DeleteTeamForm class="mt-10 sm:mt-0" :team="team" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<script>
    import TeamMemberManager from './TeamMemberManager.vue'
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import DeleteTeamForm from './DeleteTeamForm.vue'
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
    import UpdateTeamNameForm from './UpdateTeamNameForm.vue'
import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    export default {
        props: [
            'team',
            'availableRoles',
            'permissions',
        ],

        components: {
    AppLayout,
    DeleteTeamForm,
    JetSectionBorder,
    TeamMemberManager,
    UpdateTeamNameForm,
    SettingsSectionNav
},
    }
</script>
