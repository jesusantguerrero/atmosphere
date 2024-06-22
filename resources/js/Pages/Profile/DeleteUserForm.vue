<template>
    <JetActionSection
        title="Delete Account"
        description="Permanently delete your account."
    >
        <template #content>
            <div class="max-w-xl text-sm">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
            </div>

            <div class="mt-5">
                <JetDangerButton @click="confirmUserDeletion">
                    Delete Account
                </JetDangerButton>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <JetDialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    Delete Account
                </template>

                <template #content>
                    Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.

                    <div class="mt-4">
                        <jet-input type="password" class="block w-3/4 mt-1" placeholder="Password"
                                    ref="password"
                                    v-model="form.password"
                                    @keyup.enter="deleteUser" />

                        <jet-input-error :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <JetSecondaryButton @click="closeModal">
                        Cancel
                    </JetSecondaryButton>

                    <jetDangerButton class="ml-2" @click="deleteUser" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Delete Account
                    </jetDangerButton>
                </template>
            </JetDialogModal>
        </template>
    </JetActionSection>
</template>

<script>
    import JetActionSection from '@/Components/atoms/ActionSection.vue'
    import JetDialogModal from '@/Components/atoms/DialogModal.vue'
    import JetDangerButton from '@/Components/atoms/DangerButton.vue'
    import JetInput from '@/Components/atoms/Input.vue'
    import JetInputError from '@/Components/atoms/InputError.vue'
    import JetSecondaryButton from '@/Components/atoms/SecondaryButton.vue'
    import { useForm } from '@inertiajs/vue3'

    export default {
        components: {
            JetActionSection,
            JetDangerButton,
            JetDialogModal,
            JetInput,
            JetInputError,
            JetSecondaryButton,
        },

        data() {
            return {
                confirmingUserDeletion: false,

                form: useForm({
                    password: '',
                })
            }
        },

        methods: {
            confirmUserDeletion() {
                this.confirmingUserDeletion = true;

                setTimeout(() => this.$refs.password.focus(), 250)
            },

            deleteUser() {
                this.form.delete(route('current-user.destroy'), {
                    preserveScroll: true,
                    onSuccess: () => this.closeModal(),
                    onError: () => this.$refs.password.focus(),
                    onFinish: () => this.form.reset(),
                })
            },

            closeModal() {
                this.confirmingUserDeletion = false

                this.form.reset()
            },
        },
    }
</script>
