<template>
    <AppLayout title="Create Recipe" :show-back-button="true" @back="$inertia.visit('/meal-planner')">
        <template #header>
              <MealSectionNav>
                <template #actions>
                    <p v-if="meals">Editing <span class="font-bold">{{ meals.name }}</span></p>
                    <div>
                        <AtButton class="h-10 text-white bg-pink-400" rounded @click="submit()"> Save </AtButton>
                    </div>
                </template>
              </MealSectionNav>
        </template>

        <MealTemplate class="mx-auto" :show-meal-types="false">
            <MealForm
                ref="mealForm"
                :meal="meals"
            />
        </MealTemplate>
    </AppLayout>
</template>

<script setup>
    import { ref } from 'vue';
    import { AtButton } from "atmosphere-ui";
    import { Link } from "@inertiajs/inertia-vue3"
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MealForm from '@/Components/MealForm.vue';
    import MealSectionNav from '@/Components/templates/MealSectionNav.vue';
    import MealTemplate from '@/Components/templates/MealTemplate.vue';

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
