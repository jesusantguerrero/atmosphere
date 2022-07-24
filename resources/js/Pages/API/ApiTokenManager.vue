<template>
    <div>
        <!-- Generate API Token -->
        <JetFormSection
            title="Create API Token"
            description="API tokens allow third-party services to authenticate with our application on your behalf."
            @submitted="createApiToken">

            <template #form>
                <!-- Token Name -->
                <div class="col-span-6 sm:col-span-4">
                    <jet-label for="name" value="Name" />
                    <jet-input id="name" type="text" class="mt-1 block w-full" v-model="createApiTokenForm.name" autofocus />
                    <jet-input-error :message="createApiTokenForm.errors.name" class="mt-2" />
                </div>

                <!-- Token Permissions -->
                <div class="col-span-6" v-if="availablePermissions.length > 0">
                    <jet-label for="permissions" value="Permissions" />

                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="permission in availablePermissions" :key="permission">
                            <label class="flex items-center">
                                <jet-checkbox :value="permission" v-model:checked="createApiTokenForm.permissions"/>
                                <span class="ml-2 text-sm">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </template>

            <template #actions>
                <JetActionMessage :on="createApiTokenForm.recentlySuccessful" class="mr-3">
                    Created.
                </JetActionMessage>

                <JetButton :class="{ 'opacity-25': createApiTokenForm.processing }" :disabled="createApiTokenForm.processing">
                    Create
                </JetButton>
            </template>
        </JetFormSection>

        <div v-if="tokens.length > 0">
            <JetSectionBorder />

            <!-- Manage API Tokens -->
            <div class="mt-10 sm:mt-0">
                <jet-action-section>
                    <template #title>
                        Manage API Tokens
                    </template>

                    <template #description>
                        You may delete any of your existing tokens if they are no longer needed.
                    </template>

                    <!-- API Token List -->
                    <template #content>
                        <div class="space-y-6">
                            <div class="flex items-center justify-between" v-for="token in tokens" :key="token.id">
                                <div>
                                    {{ token.name }}
                                </div>

                                <div class="flex items-center">
                                    <div class="text-sm text-gray-300" v-if="token.last_used_ago">
                                        Last used {{ token.last_used_ago }}
                                    </div>

                                    <button class="cursor-pointer ml-6 text-sm text-body underline"
                                        @click="manageApiTokenPermissions(token)"
                                        v-if="availablePermissions.length > 0"
                                    >
                                        Permissions
                                    </button>

                                    <button class="cursor-pointer ml-6 text-sm text-red-500" @click="confirmApiTokenDeletion(token)">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </jet-action-section>
            </div>
        </div>

        <!-- Token Value Modal -->
        <JetDialogModal :show="displayingToken" @close="displayingToken = false">
            <template #title>
                API Token
            </template>

            <template #content>
                <div>
                    Please copy your new API token. For your security, it won't be shown again.
                </div>

                <div class="mt-4 bg-gray-100 px-4 py-2 rounded font-mono text-sm text-gray-500" v-if="$page.props.jetstream.flash.token">
                    {{ $page.props.jetstream.flash.token }}
                </div>
            </template>

            <template #footer>
                <JetSecondaryButton @click="displayingToken = false">
                    Close
                </JetSecondaryButton>
            </template>
        </JetDialogModal>

        <!-- API Token Permissions Modal -->
        <JetDialogModal :show="managingPermissionsFor" @close="managingPermissionsFor = null">
            <template #title>
                API Token Permissions
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="permission in availablePermissions" :key="permission">
                        <label class="flex items-center">
                            <JetCheckbox :value="permission" v-model:checked="updateApiTokenForm.permissions"/>
                            <span class="ml-2 text-sm text-body">{{ permission }}</span>
                        </label>
                    </div>
                </div>
            </template>

            <template #footer>
                <JetSecondaryButton @click="managingPermissionsFor = null">
                    Cancel
                </JetSecondaryButton>

                <jet-button class="ml-2" @click="updateApiToken" :class="{ 'opacity-25': updateApiTokenForm.processing }" :disabled="updateApiTokenForm.processing">
                    Save
                </jet-button>
            </template>
        </JetDialogModal>

        <!-- Delete Token Confirmation Modal -->
        <JetConfirmationModal :show="apiTokenBeingDeleted" @close="apiTokenBeingDeleted = null">
            <template #title>
                Delete API Token
            </template>

            <template #content>
                Are you sure you would like to delete this API token?
            </template>

            <template #footer>
                <jet-secondary-button @click="apiTokenBeingDeleted = null">
                    Cancel
                </jet-secondary-button>

                <jet-danger-button class="ml-2" @click="deleteApiToken" :class="{ 'opacity-25': deleteApiTokenForm.processing }" :disabled="deleteApiTokenForm.processing">
                    Delete
                </jet-danger-button>
            </template>
        </JetConfirmationModal>
    </div>
</template>

<script setup>
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
    import JetActionSection from '@/Jetstream/ActionSection.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetConfirmationModal from '@/Jetstream/ConfirmationModal.vue'
    import JetDangerButton from '@/Jetstream/DangerButton.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetCheckbox from '@/Jetstream/Checkbox.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
    import { ref } from 'vue'
    import { Inertia } from '@inertiajs/inertia'
    import { useForm } from '@inertiajs/inertia-vue3'

    const props = defineProps([
        'tokens',
        'availablePermissions',
        'defaultPermissions',
    ]);


    const createApiTokenForm = useForm({
        name: '',
        permissions: props.defaultPermissions,
    });

    const updateApiTokenForm = useForm({
        permissions: []
    });

    const deleteApiTokenForm = useForm();

    const displayingToken = ref(false);
    const managingPermissionsFor = ref(null);
    const apiTokenBeingDeleted = ref(null);

    const createApiToken = () => {
        createApiTokenForm.post(route('api-tokens.store'), {
            preserveScroll: true,
            onSuccess: () => {
                displayingToken.value = true
                createApiTokenForm.reset()
            }
        })
    }

    const manageApiTokenPermissions = (token) => {
        updateApiTokenForm.permissions = token.abilities
        managingPermissionsFor.value = token
    }

    const updateApiToken = () => {
        updateApiTokenForm.put(route('api-tokens.update', managingPermissionsFor.value), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (managingPermissionsFor.value = null),
        })
    }

    const confirmApiTokenDeletion = (token) => {
       apiTokenBeingDeleted.value = token
    }

    const deleteApiToken = () => {
        deleteApiTokenForm.delete(route('api-tokens.destroy', apiTokenBeingDeleted.value), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => (apiTokenBeingDeleted.value = null),
        })
    }
</script>
