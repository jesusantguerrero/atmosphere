<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-pink-400">
                        Meal Planner
                    </h2>
                    <span class="mt-1 text-gray-200">There a total of {{ meals.data.length || 0 }} meals</span>
                </div>

                <div>
                    <AtButton class="items-center h-10 text-white bg-pink-400" rounded @click="$inertia.visit(route('meals.create'))"> New Meal</AtButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="px-6 mx-auto md:px-0 max-w-7xl">
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
    import { reactive, toRefs } from 'vue';
    import { AtButton } from "atmosphere-ui";
    import MealSection from '@/Components/Meal';
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
