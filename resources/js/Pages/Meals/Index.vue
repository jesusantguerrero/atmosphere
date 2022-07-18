<template>
    <AppLayout title="Recipes" :show-back-button="true" @back="$inertia.visit('/meal-planner')">
        <template #header>
            <div class="flex justify-between items-center">
                <div>

                    <span class="mt-1 text-gray-200">There a total of {{ recipes.length || 0 }} meals</span>
                </div>

                <div>
                    <AtButton class="items-center h-10 text-white bg-pink-400" rounded @click="$inertia.visit(route('meals.create'))"> New Meal</AtButton>
                </div>
            </div>
        </template>
        <MealTemplate class="mx-auto">
            <MealSection
                :meals="recipes"
                @click="$inertia.visit(route('meals.edit', $event))"
            />
        </MealTemplate>
    </AppLayout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MealSection from '@/Components/Meal.vue';
    import { computed } from "vue";
    import MealTemplate from "@/Components/templates/MealTemplate.vue";

    const props = defineProps({
        meals: {
            type: Array,
            required: true
        }
    });

    const recipes = computed(() => {
        return props.meals?.data?.length ?? []
    })
</script>
