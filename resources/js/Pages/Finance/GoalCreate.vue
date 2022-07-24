<template>
    <AppLayout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <Link href="/meals" class="block text-xl font-semibold leading-tight text-primary">
                    Goals
                </Link>
                <p v-if="meals">Editing <span class="font-bold">{{ meals.name }}</span></p>

                </div>

                <div>
                    <at-button class="text-white bg-primary" @click="submit()"> Save </at-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-6 mx-auto max-w-7xl">
                <goal-form :form="form" @close="close" ref="resourceForm" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import { ref } from 'vue';
    import { AtButton } from "atmosphere-ui";
    import { Link } from "@inertiajs/inertia-vue3"
    import AppLayout from '@/Layouts/AppLayout.vue';
    import GoalForm from '@/Components/GoalForm.vue';

    defineProps({
        meals: {
            type: [Object, null],
            default: null
        }
    });

    const resourceForm = ref(null);
    const submit = () => {
        resourceForm.value.submit()
    }
</script>
