<script lang="ts" setup>
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import DeleteUserForm from './DeleteUserForm.vue'
    import LogoutOtherBrowserSessionsForm from './LogoutOtherBrowserSessionsForm.vue'
    import TwoFactorAuthenticationForm from './TwoFactorAuthenticationForm.vue'
    import UpdatePasswordForm from './UpdatePasswordForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    defineProps<{
        confirmsTwoFactorAuthentication: boolean,
        sessions: any[],
    }>();

    // Security page: password, two-factor auth, active sessions and the account
    // deletion danger zone. Its own route (/user/security).
</script>

<template>
    <AppLayout title="Settings - Security">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-5xl px-4 pt-32 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-body">{{ $t('Security') }}</h1>
                <p class="mt-1 text-sm text-body-1/60">
                    {{ $t('Password, two-factor authentication and active sessions.') }}
                </p>
            </header>

            <div class="space-y-6">
                <UpdatePasswordForm v-if="$page.props.jetstream.canUpdatePassword" />

                <TwoFactorAuthenticationForm
                    v-if="$page.props.jetstream.canManageTwoFactorAuthentication"
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                />

                <LogoutOtherBrowserSessionsForm :sessions="sessions" />

                <DeleteUserForm v-if="$page.props.jetstream.hasAccountDeletionFeatures" />
            </div>
        </div>
    </AppLayout>
</template>
