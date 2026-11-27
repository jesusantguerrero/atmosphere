<script lang="ts" setup>
    import { usePage } from '@inertiajs/vue3'
    import { computed, ref } from 'vue'

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import ConnectedAppsForm from './ConnectedAppsForm.vue'
    import DataExportForm from './DataExportForm.vue'
    import DeleteUserForm from './DeleteUserForm.vue'
    import LogoutOtherBrowserSessionsForm from './LogoutOtherBrowserSessionsForm.vue'
    import SettingsDisclosure from './SettingsDisclosure.vue'
    import SettingsNotice from './SettingsNotice.vue'
    import SettingsTile from './SettingsTile.vue'
    import TwoFactorAuthenticationForm from './TwoFactorAuthenticationForm.vue'
    import UpdatePasswordForm from './UpdatePasswordForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    const props = defineProps<{
        confirmsTwoFactorAuthentication: boolean,
        sessions: any[],
        connectedApps: any[],
    }>();

    // Security page: how the account stands first (tiles and the one missing
    // protection), then the sessions, and the rarely used forms folded away.
    const page = usePage();
    const jetstream = computed(() => page.props.jetstream);
    const twoFactorEnabled = computed(() => Boolean(page.props.auth.user.two_factor_enabled));
    const showTwoFactor = ref(false);
    const showPassword = ref(false);

    const otherSessions = computed(() => props.sessions.filter((session) => !session.is_current_device).length);
</script>

<template>
    <AppLayout :title="$t('Security')">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-5xl px-4 pt-24 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-body">{{ $t('Security') }}</h1>
                <p class="mt-1 text-sm text-body-1/70">{{ $t('Who can get into your account and from where.') }}</p>
            </header>

            <div class="flex flex-col gap-6">
                <SettingsNotice
                    v-if="jetstream.canManageTwoFactorAuthentication && !twoFactorEnabled"
                    :title="$t('One thing missing: two-step verification')"
                    :description="$t('If someone gets your password, they would still need your phone to get in.')"
                >
                    <template #icon><IMdiShieldAlertOutline /></template>
                    <LogerButton type="button" @click="showTwoFactor = true">{{ $t('Turn on now') }}</LogerButton>
                </SettingsNotice>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <SettingsTile v-if="jetstream.canUpdatePassword" :label="$t('Password')" :value="$t('Set')" tone="ok">
                        <template #icon><IMdiLockOutline /></template>
                        <LogerButton type="button" variant="ghost" class="!px-3 !py-1 text-sm" @click="showPassword = true">{{ $t('Change') }}</LogerButton>
                    </SettingsTile>
                    <SettingsTile
                        v-if="jetstream.canManageTwoFactorAuthentication"
                        :label="$t('Two-step verification')"
                        :value="twoFactorEnabled ? $t('On') : $t('Off')"
                        :tone="twoFactorEnabled ? 'ok' : 'warn'"
                    >
                        <template #icon><IMdiCellphoneKey /></template>
                        <LogerButton type="button" :variant="twoFactorEnabled ? 'ghost' : 'primary'" class="!px-3 !py-1 text-sm" @click="showTwoFactor = true">
                            {{ twoFactorEnabled ? $t('Manage') : $t('Turn on') }}
                        </LogerButton>
                    </SettingsTile>
                    <SettingsTile
                        :label="$t('Sessions')"
                        :value="$t(sessions.length === 1 ? '1 device' : '{count} devices', { count: sessions.length })"
                        :hint="otherSessions ? $t('{count} besides this one', { count: otherSessions }) : $t('Only this device')"
                    >
                        <template #icon><IMdiMonitor /></template>
                    </SettingsTile>
                    <SettingsTile
                        :label="$t('Connected apps')"
                        :value="connectedApps.length ? connectedApps.map(app => app.name).join(', ') : $t('None')"
                        :tone="connectedApps.length ? 'neutral' : 'ok'"
                        :hint="connectedApps.length ? $t('They can act on your behalf') : $t('Nobody else acts for you')"
                    >
                        <template #icon><IMdiConnection /></template>
                    </SettingsTile>
                </div>

                <LogoutOtherBrowserSessionsForm :sessions="sessions" />

                <ConnectedAppsForm v-if="connectedApps.length" :apps="connectedApps" />

                <SettingsDisclosure
                    v-if="jetstream.canManageTwoFactorAuthentication"
                    v-model:open="showTwoFactor"
                    :title="$t('Two-step verification')"
                    :hint="twoFactorEnabled ? $t('On') : $t('Off')"
                >
                    <template #icon><IMdiCellphoneKey /></template>
                    <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />
                </SettingsDisclosure>

                <SettingsDisclosure
                    v-if="jetstream.canUpdatePassword"
                    v-model:open="showPassword"
                    :title="$t('Change password')"
                    :hint="$t('Opens right here')"
                >
                    <template #icon><IMdiLockOutline /></template>
                    <UpdatePasswordForm />
                </SettingsDisclosure>

                <div class="grid gap-4 md:grid-cols-2">
                    <DataExportForm />
                    <DeleteUserForm v-if="jetstream.hasAccountDeletionFeatures" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
