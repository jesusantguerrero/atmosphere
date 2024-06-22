<script lang="ts" setup>
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import { usePage } from '@inertiajs/vue3'
    import DeleteUserForm from './DeleteUserForm.vue'
    import LogoutOtherBrowserSessionsForm from './LogoutOtherBrowserSessionsForm.vue'
    import TwoFactorAuthenticationForm from './TwoFactorAuthenticationForm.vue'
    import UpdatePasswordForm from './UpdatePasswordForm.vue'
    import UpdateProfileInformationForm from './UpdateProfileInformationForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    defineProps<{
        confirmsTwoFactorAuthentication: boolean,
        sessions: any[],
    }>();

</script>


<template>
    <AppLayout  title="Settings - Profile">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div>
            <div class="max-w-7xl mx-auto space-y-5 [&>*]:pt-5 divide-y pt-16 pb-20 sm:px-6 lg:px-8">
                <UpdateProfileInformationForm
                    v-if="$page.props.jetstream.canUpdateProfileInformation"
                    :user="$page.props.user"
                />

                <UpdatePasswordForm
                    v-if="$page.props.jetstream.canUpdatePassword"
                    class="mt-10 sm:mt-0"
                />

                <TwoFactorAuthenticationForm
                    v-if="$page.props.jetstream.canManageTwoFactorAuthentication"
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                    class="mt-10 sm:mt-0"
                />

                <LogoutOtherBrowserSessionsForm
                    :sessions="sessions"
                    class="mt-10 sm:mt-0"
                />

                 <DeleteUserForm
                    v-if="$page.props.jetstream.hasAccountDeletionFeatures"
                    class="mt-10 sm:mt-0"
                 />
            </div>
        </div>
    </AppLayout>
</template>
