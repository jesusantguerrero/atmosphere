<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <inertia-link href="/meals" class="block text-xl font-semibold leading-tight text-pink-400">
                    Meal Planner
                </inertia-link>
                <p v-if="meals">Editing <span class="font-bold">{{ meals.name }}</span></p>

                </div>

                <div>
                    <AtButton class="h-10 text-white bg-pink-400" rounded @click="submit()"> Save </AtButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-6 mx-auto max-w-7xl">
                <MealForm
                    ref="mealForm"
                    :meal="meals"
                />
            </div>
        </div>
    </app-layout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton } from "atmosphere-ui";
    import { ref } from '@vue/reactivity';
    import MealForm from '../Components/MealForm.vue';

    defineProps({
        meals: {
            type: [Object, null],
            default: null
        }
    });

    const mealForm = ref(null);
    const submit = () => {
        mealForm.value.submit()
    }
</script>
