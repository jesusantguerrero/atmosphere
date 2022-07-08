<template>
    <AppLayout title="Recipies" :show-back-button="true" @back="$inertia.visit('/meal-planner')">
        <div class="pl-6 pb-20 max-w-screen-2xl lg:pl-8">
            <div class="flex justify-between">
                <div>

                    <span class="mt-1 text-gray-200">There a total of {{ meals.data.length || 0 }} meals</span>
                </div>

                <div>
                    <AtButton class="items-center h-10 text-white bg-pink-400" rounded @click="$inertia.visit(route('meals.create'))"> New Meal</AtButton>
                </div>
            </div>
            <div class="md:px-0">
                <MealSection
                    :meals="meals.data"
                    @click="$inertia.visit(route('meals.edit', $event))"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MealSection from '@/Components/Meal.vue';

    defineProps({
            meals: {
                type: Array,
                required: true
            }
    });
</script>
