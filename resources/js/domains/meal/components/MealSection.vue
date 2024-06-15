<script setup lang="ts">
    import { computed } from "vue";
    import { useForm } from "@inertiajs/vue3";
    import {  ElMessageBox } from "element-plus";

    import MealItem from "./MealItem.vue";
    import MealItemCard from "./MealItemCard.vue";

    import { Meal } from "../models";


    const props = withDefaults(defineProps<{
        meals: Meal[];
        selected: Meal[]
        display: "grid" | "list"
    }>(), {
        display: "grid"
    });

    const isSelected = (meal: Meal) => {
        return !!props.selected
        ?.find(selectedMeal => selectedMeal.id == meal.id)
    }

    const deleteForm = useForm({
        id: 0
    });

    const deleteResource = async (meal: any) => {
        const isConfirmed = await ElMessageBox.confirm("Are you sure to delete this meal?");
        if (!isConfirmed) return

        deleteForm.delete(route('meals.destroy', { meal }))
    }

    const MealItemComponent = computed(() => {
        return props.display == 'grid' ? MealItemCard : MealItem;
    })

    const containerClass = computed(() => {
        return props.display == 'grid' ? 'grid  md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8' :  'space-y-4'
    })

</script>

<template>
    <section class="py-6" :class="containerClass">
        <MealItemComponent
            v-for="meal in meals"
            :meal="meal"
            :key="meal.id"
            compact
            :class="{'bg-primary-300 text-white': isSelected(meal)}"
            @toggle-like="$emit('toggle-like', meal)"
            @click="$emit('click', meal)"
            @tag-selected="$emit('tag-selected', ...$event)"
            @delete="deleteResource"
        />
    </section>
</template>
