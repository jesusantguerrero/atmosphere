<template>
    <AppLayout title="Recipes" :show-back-button="true" @back="$inertia.visit('/meal-planner')">
        <template #header>
            <MealSectionNav>
                <template #actions>
                   <div>
                        <AtButton class="items-center h-10 text-white bg-primary" rounded @click="$inertia.visit(route('meals.create'))"> New Meal</AtButton>
                    </div>
                </template>
            </MealSectionNav>
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
    import AppLayout from '@/Components/templates/AppLayout.vue';
    import MealSection from '@/Components/MealSection.vue';
    import { computed } from "vue";
    import MealTemplate from "@/Components/templates/MealTemplate.vue";
    import MealSectionNav from "@/Components/templates/MealSectionNav.vue";

    const props = defineProps({
        meals: {
            type: Array,
            required: true
        }
    });

    const recipes = computed(() => {
        return props.meals?.data ?? []
    })
</script>
