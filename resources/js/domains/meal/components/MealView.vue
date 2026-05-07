<script setup lang="ts">
    import { useForm, router } from "@inertiajs/vue3"
    import { computed } from 'vue'

    import MealFormLine from "./MealFormLine.vue";
    import WidgetContainer from "@/Components/WidgetContainer.vue";
    import LogerButton from "@/Components/atoms/LogerButton.vue";

    defineEmits(['close', 'toggle-like']);
    const props = defineProps({
        meal: {
            type: Object,
            default: () => ({
                name: '',
            }),
        }
    });

    const form = useForm({
        ...{
            name: props.meal?.name,
        },
        ingredients: props.meal ? [...props.meal.ingredients, {}]: [{}]
    })

    const checkIngredients = (index: number, value: string) => {
        if (form.ingredients.length == index + 1 && value) {
            form.ingredients.push({
                unit: 'unit',
                quantity: 1,
                product_id: "",
                name: ''
            })
        }
    }

    const mealSections = computed(() => [
    {
        name: "ingredients",
        label: "Ingredients",
    },
    {
        name: "recipe",
        label: "Recipe",
    },
    {
        name: "instructions",
        label: "Instructions",
    }]);

    const addToShoppingList = () => {
        router.post(`/meals/${props.meal.id}/shopping-list`)
    }
</script>

<template>
    <article class="text-body">
        <section class="rounded-t-md h-64 w-full bg-gray-300 relative">
            <span class="absolute bg-primary/10 font-bold text-sm text-primary top-2.5 right-5 rounded-md px-2 capitalize">
                {{ meal.meal_type }}
            </span>
            <button
                type="button"
                class="absolute top-2.5 left-2.5 w-9 h-9 flex items-center justify-center rounded-full bg-white/80 hover:bg-white shadow-md transition"
                :class="meal.is_liked ? 'text-error' : 'text-body-1/60 hover:text-error'"
                :title="meal.is_liked ? 'Remove from favorites' : 'Add to favorites'"
                @click.stop="$emit('toggle-like', meal)"
            >
                <i class="fa-heart" :class="meal.is_liked ? 'fas' : 'far'" />
            </button>
        </section>

        <WidgetContainer
          :message="meal.name"
          :tabs="mealSections"
          :default-tab="mealSections[0].name"
          class="order-2 mt-4 lg:mt-0 lg:order-1"
        >

        <template #ingredients>
            <section class="pb-4">
                <MealFormLine v-for="(ingredient, index) in form.ingredients"
                    view-only
                    :key="`${ingredient.id}-${index}`"
                    :meal="meal"
                    :index="index"
                    :ingredient="ingredient"
                    @check="checkIngredients(index, $event)"
                />
                <LogerButton class="mt-4 w-full text-center justify-center" variant="secondary" @click="addToShoppingList">
                    Add to shopping list
                </LogerButton>
            </section>
        </template>

        <template #recipe>
            <section v-if="meal.notes"> {{ meal.notes }}</section>
            <section v-else class="text-center pb-6 text-2xl font-bold text-body-1/20 first-letter:capitalize"> no description</section>
        </template>

        <template #instructions>
            <section v-if="meal.instructions"> {{ meal.instructions }}</section>
            <section v-else class="text-center pb-6 text-2xl font-bold text-body-1/20 first-letter:capitalize">
                no instructions
            </section>
        </template>

        </WidgetContainer>
    </article>
</template>
