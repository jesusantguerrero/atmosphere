<template>
    <div>
        <!-- Generate API Token -->
        <JetFormSection
            title="Create API Token"
            description="API tokens allow third-party services to authenticate with our application on your behalf."
            @submitted="createApiToken">

            <template #form>
                <!-- Token Name -->
                <AtField class="col-span-6 sm:col-span-4" label="Name" field="name" :errors="createApiTokenForm.errors">
                    <LogerInput id="name" type="text" v-model="createApiTokenForm.name" autofocus />
                </AtField>

                <!-- Token Permissions -->
                <AtField class="col-span-6" v-if="availablePermissions.length > 0" label="Permissions" field="permissions">
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="permission in availablePermissions" :key="permission">
                            <label class="flex items-center">
                                <jet-checkbox :value="permission" v-model:checked="createApiTokenForm.permissions"/>
                                <span class="ml-2 text-sm">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </AtField>
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
                <JetActionSection title="Manage API Tokens" description="You may delete any of your existing tokens if they are no longer needed.">
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
                </JetActionSection>
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
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import JetActionSection from '@/Components/atoms/ActionSection.vue'
    import JetButton from '@/Components/atoms/Button.vue'
    import JetConfirmationModal from '@/Components/atoms/ConfirmationModal.vue'
    import JetDangerButton from '@/Components/atoms/DangerButton.vue'
    import JetDialogModal from '@/Components/atoms/DialogModal.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetCheckbox from '@/Components/atoms/Checkbox.vue'
    import JetSecondaryButton from '@/Components/atoms/SecondaryButton.vue'
    import JetSectionBorder from '@/Components/atoms/SectionBorder.vue'
    import { ref } from 'vue'
    import { router } from '@inertiajs/vue3'
    import { useForm } from '@inertiajs/vue3'
    import { AtField, AtButton } from "atmosphere-ui"
    import LogerInput from '@/Components/atoms/LogerInput.vue'

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

    const deleteApiTokenForm = useForm({});

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
