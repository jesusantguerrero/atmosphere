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
            <MealSection :meals="productData" @tag-selected="assignProductLabel" />
        </MealTemplate>
    </AppLayout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout.vue';
    import MealSection from '@/Components/Meal.vue';
    import { computed } from "vue";
    import MealTemplate from "@/Components/templates/MealTemplate.vue";
    import MealSectionNav from "@/Components/templates/MealSectionNav.vue";
    import { generateRandomColor } from "@/utils";

    const props = defineProps({
        products: {
            type: Array,
            required: true
        }
    });

    const productData = computed(() => {
        return props.products?.data ?? []
    })


    const assignProductLabel = (label, product) => {
        axios.post(`/api/ingredients/${product.id}/labels`, {
            ...label,
            color: generateRandomColor()
        })
    }
</script>
