<script setup lang="ts">
    import { useForm } from '@inertiajs/vue3'
    import { router } from '@inertiajs/vue3'
    import { reactive } from 'vue'
    // @ts-ignore
    import { AtField, AtButton } from "atmosphere-ui"

    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import JetActionSection from '@/Components/atoms/ActionSection.vue'
    import JetButton from '@/Components/atoms/Button.vue'
    import JetConfirmationModal from '@/Components/atoms/ConfirmationModal.vue'
    import JetDangerButton from '@/Components/atoms/DangerButton.vue'
    import JetDialogModal from '@/Components/atoms/DialogModal.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetSecondaryButton from '@/Components/atoms/SecondaryButton.vue'
    import JetSectionBorder from '@/Components/atoms/SectionBorder.vue'
    import LogerInput from '@/Components/atoms/LogerInput.vue'
import LogerButton from '@/Components/atoms/LogerButton.vue'


    const props = defineProps({
        team: Object,
        availableRoles: {
            type: Array,
            default() {
                return []
            }
        },
        userPermissions: Object,
    });

    const addTeamMemberForm = useForm({
        email: '',
        role: null,
    });

    const updateRoleForm = useForm({
        role: null,
    });

    const leaveTeamForm = useForm({});
    const removeTeamMemberForm = useForm({});

    const state = reactive({
        currentlyManagingRole: false,
        managingRoleFor: null,
        confirmingLeavingTeam: false,
        teamMemberBeingRemoved: null,
    });

    function addTeamMember() {
        addTeamMemberForm.post(route('team-members.store', props.team), {
            errorBag: 'addTeamMember',
            preserveScroll: true,
            onSuccess: () => addTeamMemberForm.reset(),
        });
    }

    function resendTeamInvitation(invitation) {
        useForm({}).put(route('team-invitations.resend', invitation), {
            preserveScroll: true,
        });
    }

    function cancelTeamInvitation(invitation) {
        router.delete(route('team-invitations.destroy', invitation), {
            preserveScroll: true
        });
    }

    function manageRole(teamMember) {
        state.managingRoleFor = teamMember
        updateRoleForm.role = teamMember.membership.role
        state.currentlyManagingRole = true
    }

    function updateRole() {
        updateRoleForm.put(route('team-members.update', [props.team, state.managingRoleFor]), {
            preserveScroll: true,
            onSuccess: () => (this.currentlyManagingRole = false),
        })
    }

    function confirmLeavingTeam() {
        state.confirmingLeavingTeam = true
    }

    function leaveTeam() {
        leaveTeamForm.delete(route('team-members.destroy', [this.team, this.$page.props.user]))
    }

    function confirmTeamMemberRemoval(teamMember) {
        state.teamMemberBeingRemoved = teamMember
    }

    function removeTeamMember() {
        removeTeamMemberForm.delete(route('team-members.destroy', [this.team, this.teamMemberBeingRemoved]), {
            errorBag: 'removeTeamMember',
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (this.teamMemberBeingRemoved = null),
        })
    }

    function displayableRole(role) {
        return props.availableRoles.find(r => r.key === role).name
    };
</script>


<template>
    <div>
        <div v-if="userPermissions?.canAddTeamMembers">
            <JetSectionBorder />

            <!-- Add Team Member -->
            <JetFormSection
                title="Add Budget Member"
                description="Add a member to your budget, allowing them to collaborate with you."
                @submitted="addTeamMember"
            >
                <template #form>
                    <div class="col-span-6">
                        <div class="max-w-xl text-sm text-body-1">
                            Please provide the email address of the new member.
                        </div>
                    </div>

                    <!-- Member Email -->
                    <AtField
                        class="col-span-6 sm:col-span-4" label="Email" field="email"
                        :errors="addTeamMemberForm.errors"
                    >
                        <LogerInput id="email" type="email" v-model="addTeamMemberForm.email" />
                    </AtField>

                    <!-- Role -->
                    <AtField
                        class="col-span-6 lg:col-span-4"
                        v-if="availableRoles.length > 0"
                        field="roles"
                        label="Role"
                        :errors="addTeamMemberForm.errors"
                    >
                        <div class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer">
                            <button
                                v-for="(role, i) in availableRoles"
                                type="button"
                                class="relative inline-flex w-full px-4 py-3 rounded-lg focus:z-10 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200"
                                :class="{'border-t border-gray-200 rounded-t-none': i > 0, 'rounded-b-none': i != Object.keys(availableRoles).length - 1}"
                                @click="addTeamMemberForm.role = role.key"
                                :key="role.key"
                            >
                                <div :class="{'opacity-50': addTeamMemberForm.role && addTeamMemberForm.role != role.key}">
                                    <!-- Role Name -->
                                    <div class="flex items-center">
                                        <div class="text-sm text-gray-600" :class="{'font-semibold': addTeamMemberForm.role == role.key}">
                                            {{ role.name }}
                                        </div>

                                        <svg v-if="addTeamMemberForm.role == role.key" class="w-5 h-5 ml-2 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>

                                    <!-- Role Description -->
                                    <div class="mt-2 text-xs text-gray-600">
                                        {{ role.description }}
                                    </div>
                                </div>
                            </button>
                        </div>
                    </AtField>
                </template>

                <template #actions>
                    <JetActionMessage :on="addTeamMemberForm.recentlySuccessful" class="mr-3">
                        Added.
                    </JetActionMessage>

                    <LogerButton type="primary" :is-loading="addTeamMemberForm.processing" :disabled="addTeamMemberForm.processing">
                        Add
                    </LogerButton>
                </template>
            </JetFormSection>
        </div>

        <div v-if="team.team_invitations.length > 0 && userPermissions?.canAddTeamMembers">
            <JetSectionBorder />

            <!-- Team Member Invitations -->
            <JetActionSection class="mt-10 sm:mt-0" title=" Pending Team Invitations" description="These people have been invited to your team and have been sent an invitation email. They may join the team by accepting the email invitation.">
                <!-- Pending Team Member Invitation List -->
                <template #content>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between" v-for="invitation in team.team_invitations" :key="invitation.id">
                            <div class="text-gray-600">{{ invitation.email }}</div>

                            <div class="flex items-center space-x-2">
                                <!-- Cancel Team Invitation -->
                                <button class="ml-6 text-sm text-red-500 cursor-pointer focus:outline-none"
                                    @click="cancelTeamInvitation(invitation)"
                                    v-if="userPermissions.canRemoveTeamMembers"
                                >
                                    Cancel
                                </button>
                                <AtButton class="text-white rounded-lg bg-primary"
                                    @click="resendTeamInvitation(invitation)"
                                    v-if="userPermissions.canRemoveTeamMembers"
                                >
                                    Send Invitation
                                </AtButton>
                            </div>
                        </div>
                    </div>
                </template>
            </JetActionSection>
        </div>

        <div v-if="team.users.length > 0">
            <jet-section-border />

            <!-- Manage Team Members -->
            <jet-action-section class="mt-10 sm:mt-0">
                <template #title>
                    Team Members
                </template>

                <template #description>
                    All of the people that are part of this team.
                </template>

                <!-- Team Member List -->
                <template #content>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between" v-for="user in team.users" :key="user.id">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full" :src="user.profile_photo_url" :alt="user.name">
                                <div class="ml-4">{{ user.name }}</div>
                            </div>

                            <div class="flex items-center">
                                <!-- Manage Team Member Role -->
                                <button class="ml-2 text-sm text-gray-400 underline"
                                        @click="manageRole(user)"
                                        v-if="userPermissions?.canAddTeamMembers && availableRoles.length">
                                    {{ displayableRole(user.membership.role) }}
                                </button>

                                <div class="ml-2 text-sm text-gray-400" v-else-if="availableRoles.length">
                                    {{ displayableRole(user.membership.role) }}
                                </div>

                                <!-- Leave Team -->
                                <button class="ml-6 text-sm text-red-500 cursor-pointer"
                                                    @click="confirmLeavingTeam"
                                                    v-if="$page.props.user.id === user.id">
                                    Leave
                                </button>

                                <!-- Remove Team Member -->
                                <button class="ml-6 text-sm text-red-500 cursor-pointer"
                                                    @click="confirmTeamMemberRemoval(user)"
                                                    v-if="userPermissions.canRemoveTeamMembers">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </jet-action-section>
        </div>

        <!-- Role Management Modal -->
        <JetDialogModal :show="currentlyManagingRole" @close="currentlyManagingRole = false">
            <template #title>
                Manage Role
            </template>

            <template #content>
                <div v-if="managingRoleFor">
                    <div class="relative z-0 mt-1 border border-gray-200 rounded-lg cursor-pointer">
                        <button type="button" class="relative inline-flex w-full px-4 py-3 rounded-lg focus:z-10 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200"
                                        :class="{'border-t border-gray-200 rounded-t-none': i > 0, 'rounded-b-none': i !== Object.keys(availableRoles).length - 1}"
                                        @click="updateRoleForm.role = role.key"
                                        v-for="(role, i) in availableRoles"
                                        :key="role.key">
                            <div :class="{'opacity-50': updateRoleForm.role && updateRoleForm.role !== role.key}">
                                <!-- Role Name -->
                                <div class="flex items-center">
                                    <div class="text-sm text-gray-600" :class="{'font-semibold': updateRoleForm.role === role.key}">
                                        {{ role.name }}
                                    </div>

                                    <svg v-if="updateRoleForm.role === role.key" class="w-5 h-5 ml-2 text-green-400" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>

                                <!-- Role Description -->
                                <div class="mt-2 text-xs text-gray-600">
                                    {{ role.description }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            <template #footer>
                <jet-secondary-button @click="currentlyManagingRole = false">
                    Cancel
                </jet-secondary-button>

                <jet-button class="ml-2" @click="updateRole" :class="{ 'opacity-25': updateRoleForm.processing }" :disabled="updateRoleForm.processing">
                    Save
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- Leave Team Confirmation Modal -->
        <JetConfirmationModal :show="confirmingLeavingTeam" @close="confirmingLeavingTeam = false">
            <template #title>
                Leave Team
            </template>

            <template #content>
                Are you sure you would like to leave this team?
            </template>

            <template #footer>
                <jet-secondary-button @click="confirmingLeavingTeam = false">
                    Cancel
                </jet-secondary-button>

                <jet-danger-button class="ml-2" @click="leaveTeam" :class="{ 'opacity-25': leaveTeamForm.processing }" :disabled="leaveTeamForm.processing">
                    Leave
                </jet-danger-button>
            </template>
        </JetConfirmationModal>

        <!-- Remove Team Member Confirmation Modal -->
        <JetConfirmationModal :show="teamMemberBeingRemoved" @close="teamMemberBeingRemoved = null">
            <template #title>
                Remove Team Member
            </template>

            <template #content>
                Are you sure you would like to remove this person from the team?
            </template>

            <template #footer>
                <jet-secondary-button @click="teamMemberBeingRemoved = null">
                    Cancel
                </jet-secondary-button>

                <jet-danger-button class="ml-2" @click="removeTeamMember" :class="{ 'opacity-25': removeTeamMemberForm.processing }" :disabled="removeTeamMemberForm.processing">
                    Remove
                </jet-danger-button>
            </template>
        </JetConfirmationModal>
    </div>
</template>

