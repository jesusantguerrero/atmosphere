<script setup lang="ts">
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import CreateTeamForm from './CreateTeamForm.vue'
</script>

<template>
    <AppLayout title="Create Space">
        <template #header>
            <h2 class="px-4 py-2 text-xl font-semibold leading-tight text-gray-800">
                Create Space
            </h2>
        </template>


        <main class="py-10 mx-auto mt-8 max-w-7xl sm:px-6 lg:px-8">
            <CreateTeamForm />
        </main>
    </AppLayout>
</template>
