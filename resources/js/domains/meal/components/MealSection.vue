<script setup lang="ts">
    import { computed } from "vue";
    import { useForm } from "@inertiajs/vue3";
    import { useI18n } from "vue-i18n";
    import {  ElMessageBox } from "element-plus";

    const { t } = useI18n();

    import MealItem from "./MealItem.vue";
    import MealItemCard from "./MealItemCard.vue";

    import { Meal } from "../models";


    const props = withDefaults(defineProps<{
        meals: Meal[];
        selected: Meal[]
        display: "grid" | "list"
        resource?: "meal" | "ingredient"
    }>(), {
        display: "grid",
        resource: "meal"
    });

    const isSelected = (meal: Meal) => {
        return !!props.selected
        ?.find(selectedMeal => selectedMeal.id == meal.id)
    }

    const deleteForm = useForm({
        id: 0
    });

    const deleteResource = async (item: Meal) => {
        const isIngredient = props.resource == "ingredient";
        const isConfirmed = await ElMessageBox.confirm(
            t(isIngredient ? "Are you sure to delete this ingredient?" : "Are you sure to delete this meal?")
        ).catch(() => false);
        if (!isConfirmed) return

        deleteForm.id = item.id;
        deleteForm.delete(isIngredient
            ? route('ingredients.destroy', { ingredient: item.id })
            : route('meals.destroy', { meal: item.id }), {
            preserveScroll: true,
            onFinish: () => { deleteForm.id = 0 }
        })
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
            :deleting="deleteForm.processing && deleteForm.id == meal.id"
            @toggle-like="$emit('toggle-like', meal)"
            @click="$emit('click', meal)"
            @tag-selected="$emit('tag-selected', ...$event)"
            @delete="deleteResource"
        />
    </section>
</template>
