<template>
    <AppLayout title="Settings - Api Tokens">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-7xl mx-auto pt-16 pb-20 sm:px-6 lg:px-8">
            <ApiTokenManager
                :tokens="tokens"
                :available-permissions="availablePermissions"
                :default-permissions="defaultPermissions"
            />
        </div>
    </AppLayout>
</template>

<script>
    import ApiTokenManager from './ApiTokenManager.vue'
    import AppLayout from '@/Components/templates/AppLayout.vue'
import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    export default {
        props: [
            'tokens',
            'availablePermissions',
            'defaultPermissions',
        ],

        components: {
    ApiTokenManager,
    AppLayout,
    SettingsSectionNav
},
    }
</script>
