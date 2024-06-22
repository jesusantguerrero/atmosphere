<script setup lang="ts">
    import AppLayout from '@/Components/templates/AppLayout.vue'
    import CreateTeamForm from './CreateTeamForm.vue'

    defineProps<{
        modules: []
    }>();
</script>

<template>
    <AppLayout title="Create new space">
        <template #header>
            <h2 class="px-4 py-2 text-xl font-semibold leading-tight text-gray-800">
                Create new space
            </h2>
        </template>


        <main class="py-10 mx-auto mt-8 max-w-7xl sm:px-6 lg:px-8">
            <CreateTeamForm
                :modules="modules"
            />
        </main>
    </AppLayout>
</template>
