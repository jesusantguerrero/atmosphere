<script lang="ts" setup>
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import UpdateProfileInformationForm from './UpdateProfileInformationForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    // Account page: identity only (photo, name, email, language). Security and
    // Preferences are their own routes/pages, reachable from the top settings nav.
</script>

<template>
    <AppLayout title="Settings - Account">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-5xl px-4 pt-32 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-body">{{ $t('Account') }}</h1>
                <p class="mt-1 text-sm text-body-1/60">
                    {{ $t('Update your photo, name and language.') }}
                </p>
            </header>

            <UpdateProfileInformationForm
                v-if="$page.props.jetstream.canUpdateProfileInformation"
                :user="$page.props.auth.user"
            />
        </div>
    </AppLayout>
</template>
