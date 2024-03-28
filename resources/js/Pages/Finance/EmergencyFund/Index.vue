<script setup>
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import { usePage } from '@inertiajs/vue3'
    import BudgetFundForm from './Partials/BudgetFundForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    defineProps(['sessions'])
    const pageProps = usePage().props
</script>


<template>
    <AppLayout  title="Emergency Fund Builder">
        <template #header>
            <SettingsSectionNav />
        </template>

        <section  class="max-w-7xl mx-auto space-y-5 [&>*]:pt-5 divide-y pt-16 pb-20 sm:px-6 lg:px-8">
            <BudgetFundForm :user="pageProps.user" />
        </section>
    </AppLayout>
</template>

