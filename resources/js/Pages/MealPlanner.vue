<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <h2 class="text-xl font-semibold leading-tight text-pink-500">
                    Meal Planner
                </h2>
                <span>There a total of {{ meals.data.length || 0 }} meals</span>

                </div>

                <div>
                    <at-button class="text-white bg-pink-400" @click="openModal()"> New Meal</at-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-6 mx-auto max-w-7xl">
                <MealSection
                    :meals="meals.data"
                    @click="$inertia.visit(route('meals.edit', $event))"
                />
                <meal-modal
                    :show="isModalOpen"
                    :closeable="true"
                    @close="isModalOpen=false"
                    title="Add a new Meal"
                />
            </div>
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton } from "atmosphere-ui";
    import MealSection from '@/Components/Meal';
    import { reactive, toRefs } from '@vue/reactivity';
    import MealModal from '../Components/MealModal.vue';

    export default {
        components: {
            AppLayout,
            MealSection,
            AtButton,
            MealModal
        },
        props: {
            meals: {
                type: Array,
                required: true
            }
        },
        setup() {
            const state = reactive({
                isModalOpen: false
            })

            const openModal = () => {
                state.isModalOpen = true
            }

            return {
                ...toRefs(state),
                openModal
            }
        }
    }
</script>
