<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <inertia-link href="/meals" class="block text-xl font-semibold leading-tight text-pink-500">
                    Goals
                </inertia-link>
                <p v-if="meals">Editing <span class="font-bold">{{ meals.name }}</span></p>

                </div>

                <div>
                    <at-button class="text-white bg-pink-400" @click="submit()"> Save </at-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-6 mx-auto max-w-7xl">
                <goal-form :form="form" @close="close" ref="resourceForm" />
            </div>
        </div>
    </app-layout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton } from "atmosphere-ui";
    import { ref } from '@vue/reactivity';
    import GoalForm from '../Components/GoalForm.vue';

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
