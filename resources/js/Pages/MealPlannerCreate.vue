<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <inertia-link href="/meals" class="block text-xl font-semibold leading-tight text-pink-500">
                    Meal Planner
                </inertia-link>
                <p>Editing <span class="font-bold">{{ meals.name }}</span></p>

                </div>

                <div>
                    <at-button class="text-white bg-pink-400" @click="submit()"> Save </at-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6">
                <meal-form
                    ref="mealForm"
                    :meal="meals"
                />
            </div>
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton } from "atmosphere-ui/dist/atmosphere-ui.es.js";
    import MealSection from '@/Components/Meal';
    import { reactive, ref, toRefs } from '@vue/reactivity';
    import MealModal from '../Components/MealModal.vue';
    import MealForm from '../Components/MealForm.vue';

    export default {
        components: {
            AppLayout,
            MealSection,
            AtButton,
            MealModal,
            MealForm
        },

        props: {
            meals: {
                type: Object,
            }
        },
        setup() {
            const state = reactive({
                isModalOpen: false
            })

            const mealForm = ref(null);
            const submit = () => {
                mealForm.value.submit()
            }

            return {
                ...toRefs(state),
                mealForm,
                submit
            }
        }
    }
</script>
