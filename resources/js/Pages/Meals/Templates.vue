<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import MealTemplate from '@/domains/meal/components/MealTemplate.vue';
import MealSectionNav from '@/domains/meal/components/MealSectionNav.vue';
import MealMenuCard from '@/domains/meal/components/MealMenuCard.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

type Menu = {
    id: number;
    name: string;
    description: string | null;
    meal_plans_count: number;
};

defineProps<{ templates: Menu[] }>();
</script>

<template>
    <AppLayout title="Menu Templates">
        <template #header>
            <MealSectionNav />
        </template>
        <MealTemplate :show-meal-types="false">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-body">Menu Templates</h1>
                <p class="mt-1 text-sm text-body-1 opacity-70">
                    Reusable weekly meal plans. Use one to populate any week in the Planner.
                </p>
            </div>

            <section
                v-if="templates.length"
                class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8"
            >
                <MealMenuCard
                    v-for="menu in templates"
                    :key="menu.id"
                    :menu="menu"
                />
            </section>

            <div v-else class="flex flex-col items-center justify-center py-20 text-center">
                <p class="text-4xl mb-4">🍽️</p>
                <h2 class="text-lg font-semibold text-body">No templates yet</h2>
                <p class="mt-1 text-sm text-body-1 opacity-60 max-w-sm">
                    Go to the Planner, set up a week of meals, then click "Save as Menu" to create your first template.
                </p>
                <LogerButton
                    variant="secondary"
                    class="mt-6 h-10 text-sm"
                    @click="router.visit(route('meal-planner.index'))"
                >
                    Go to Planner
                </LogerButton>
            </div>
        </MealTemplate>
    </AppLayout>
</template>
