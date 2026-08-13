<script lang="ts" setup>
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import UpdateModulesForm from './UpdateModulesForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    // Preferences page: personal nav module visibility. Its own route
    // (/user/preferences).
</script>

<template>
    <AppLayout title="Settings - Preferences">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-5xl px-4 pt-32 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-body">{{ $t('Preferences') }}</h1>
                <p class="mt-1 text-sm text-body-1/60">
                    {{ $t('Choose which modules appear in your navigation.') }}
                </p>
            </header>

            <div id="modules"><UpdateModulesForm /></div>
        </div>
    </AppLayout>
</template>
