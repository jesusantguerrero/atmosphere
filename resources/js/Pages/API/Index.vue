<template>
    <AppLayout title="Settings - Api Tokens">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="pt-16 pb-20 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <ApiTokenManager
                :tokens="tokens"
                :available-permissions="availablePermissions"
                :default-permissions="defaultPermissions"
            />

            <section>
                <button @click="allowMessages()">Allow Message</button>
            </section>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
    import ApiTokenManager from './ApiTokenManager.vue'
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'
    import { router } from '@inertiajs/vue3';

    const props = defineProps([
        'tokens',
        'availablePermissions',
        'defaultPermissions',
        'user'
    ])
</script>
