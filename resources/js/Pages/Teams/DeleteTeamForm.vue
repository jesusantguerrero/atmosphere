<template>
    <JetActionSection title="Delete Budget" description="Permanently delete this budget space.">
        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Once a team is deleted, all of its resources and data will be permanently deleted. Before deleting this team, please download any data or information regarding this team that you wish to retain.
            </div>

            <div class="mt-5">
                <JetDangerButton @click="confirmTeamDeletion">
                    Delete Budget
                </JetDangerButton>
            </div>

            <!-- Delete Team Confirmation Modal -->
            <JetConfirmationModal :show="confirmingTeamDeletion" @close="confirmingTeamDeletion = false">
                <template #title>
                    Delete Budget
                </template>

                <template #content>
                    Are you sure you want to delete this team? Once a budget space is deleted, all of its resources and data will be permanently deleted.
                </template>

                <template #footer>
                    <jet-secondary-button @click="confirmingTeamDeletion = false">
                        Cancel
                    </jet-secondary-button>

                    <jet-danger-button class="ml-2" @click="deleteTeam" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Delete Budget
                    </jet-danger-button>
                </template>
            </JetConfirmationModal>
        </template>
    </JetActionSection>
</template>

<script lang="ts">
    import { useForm } from '@inertiajs/vue3'
    
    import JetActionSection from '@/Components/atoms/ActionSection.vue'
    import JetConfirmationModal from '@/Components/atoms/ConfirmationModal.vue'
    import JetDangerButton from '@/Components/atoms/DangerButton.vue'
    import JetSecondaryButton from '@/Components/atoms/SecondaryButton.vue'

    export default {
        props: ['team'],

        components: {
            JetActionSection,
            JetConfirmationModal,
            JetDangerButton,
            JetSecondaryButton,
        },

        data() {
            return {
                confirmingTeamDeletion: false,
                deleting: false,
                form: useForm({})
            }
        },

        methods: {
            confirmTeamDeletion() {
                this.confirmingTeamDeletion = true
            },

            deleteTeam() {
                this.form.delete(route('teams.destroy', this.team), {
                    errorBag: 'deleteTeam'
                });
            },
        },
    }
</script>
