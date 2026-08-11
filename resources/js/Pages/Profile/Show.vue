<script lang="ts" setup>
    import { ref, onMounted } from 'vue'
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import DeleteUserForm from './DeleteUserForm.vue'
    import LogoutOtherBrowserSessionsForm from './LogoutOtherBrowserSessionsForm.vue'
    import TwoFactorAuthenticationForm from './TwoFactorAuthenticationForm.vue'
    import UpdateModulesForm from './UpdateModulesForm.vue'
    import UpdatePasswordForm from './UpdatePasswordForm.vue'
    import UpdateProfileInformationForm from './UpdateProfileInformationForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    defineProps<{
        confirmsTwoFactorAuthentication: boolean,
        sessions: any[],
    }>();

    // Split the old grab-bag profile view into purpose-grouped tabs: identity
    // (Account), security (password/2FA/sessions/danger zone) and personal
    // preferences (nav modules). Client-side tabs keep every form mounted (state
    // survives switching) and sync to the URL hash so a tab is linkable and
    // survives a reload — no extra backend routes needed.
    const tabs = [
        { key: 'account', label: 'Account' },
        { key: 'security', label: 'Security' },
        { key: 'preferences', label: 'Preferences' },
    ] as const;

    const activeTab = ref<string>('account');

    const setTab = (key: string): void => {
        activeTab.value = key;
        if (typeof history !== 'undefined') history.replaceState(null, '', '#' + key);
    };

    onMounted(() => {
        const hash = window.location.hash.replace('#', '');
        if (tabs.some((t) => t.key === hash)) activeTab.value = hash;
    });
</script>

<template>
    <AppLayout title="Settings - Profile">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-3xl px-4 pt-16 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-body">{{ $t('Profile') }}</h1>
                <p class="mt-1 text-sm text-body-1/60">
                    {{ $t('Manage your account, security and preferences.') }}
                </p>
            </header>

            <!-- Purpose sub-nav -->
            <nav class="flex w-full gap-1 p-1 mb-6 rounded-xl bg-base-lvl-2 sm:w-max">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="flex-1 px-4 py-2 text-sm font-semibold transition rounded-lg sm:flex-none"
                    :class="activeTab === tab.key
                        ? 'bg-base-lvl-3 text-body shadow-sm'
                        : 'text-body-1/60 hover:text-body'"
                    @click="setTab(tab.key)"
                >
                    {{ $t(tab.label) }}
                </button>
            </nav>

            <!-- Account -->
            <div v-show="activeTab === 'account'" class="space-y-6">
                <UpdateProfileInformationForm
                    v-if="$page.props.jetstream.canUpdateProfileInformation"
                    :user="$page.props.auth.user"
                />
            </div>

            <!-- Security -->
            <div v-show="activeTab === 'security'" class="space-y-6">
                <UpdatePasswordForm v-if="$page.props.jetstream.canUpdatePassword" />

                <TwoFactorAuthenticationForm
                    v-if="$page.props.jetstream.canManageTwoFactorAuthentication"
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                />

                <LogoutOtherBrowserSessionsForm :sessions="sessions" />

                <DeleteUserForm v-if="$page.props.jetstream.hasAccountDeletionFeatures" />
            </div>

            <!-- Preferences -->
            <div v-show="activeTab === 'preferences'" class="space-y-6">
                <UpdateModulesForm />
            </div>
        </div>
    </AppLayout>
</template>
