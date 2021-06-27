<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Meal Planner
                </h2>
                <span>Editing {{ meals.name }} </span>

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
    import { AtButton } from "atmosphere-ui";
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
