<script setup lang="ts">
    import { router, useForm } from "@inertiajs/vue3"
    import { AtField } from "atmosphere-ui"
    import { nextTick } from '@vue/runtime-core'
    import LogerInput from "@/Components/atoms/LogerInput.vue";
    import MealFormLine from "./MealFormLine.vue";

    defineEmits(['close']);
    const props = defineProps({
        meal: {
            type: Object,
            default: () => ({
                name: '',
            }),
        }
    });

    const form = useForm({
        name: props.meal?.name,
        notes: props.meal?.notes ?? '',
        prep_minutes: props.meal?.prep_minutes || null,
        cook_minutes: props.meal?.cook_minutes || null,
        servings: props.meal?.servings || null,
        ingredients: props.meal ? [...(props.meal.ingredients ?? []), {}]: [{}]
    })

    const submit = async (redirectTo: string = "") => {
        const method = props.meal ? 'put' : 'post';
        const url = props.meal ? `/meals/${props.meal.id}` : '/meals';
        form
            .transform(data => ({
                ...data,
                prep_minutes: Number(data.prep_minutes) || 0,
                cook_minutes: Number(data.cook_minutes) || 0,
                servings: Number(data.servings) || 0,
                ingredients: data.ingredients.filter( ingredient => ingredient.name)
        }))
        [method](url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: async () => {
                if (method == 'post') {
                    await nextTick()
                    form.reset()
                    if (redirectTo) {
                        router.visit(redirectTo)
                    }
                }
            }
        })
    }

    const checkIngredients = (index, value) => {
        if (form.ingredients.length == index + 1 && value) {
            form.ingredients.push({
                unit: 'unit',
                quantity: 1,
                product_id: "",
                name: ''
            })
        }
    }

    const removeIngredient = (index) => {
        form.ingredients.splice(index, 1);
        nextTick(() => {
            const last = form.ingredients.length - 1;
            if (last < 0 || form.ingredients[last].name) {
                checkIngredients(last, true);
            }
        })
    }

    defineExpose({
        submit,
    })
</script>

<template>
    <div class="text-body">
        <AtField
            :label="$t('Title')"
        >
            <LogerInput v-model="form.name" rounded />
        </AtField>

        <AtField
            :label="$t('Ingredients')"
        >
            <MealFormLine v-for="(ingredient, index) in form.ingredients"
                :key="`${ingredient.id}-${index}`"
                :meal="meal"
                :index="index"
                :ingredient="ingredient"
                @check="checkIngredients(index, $event)"
                @remove="removeIngredient"
            />
        </AtField>
        <div class="grid grid-cols-3 gap-4">
            <AtField :label="$t('Prep (min)')">
                <LogerInput v-model="form.prep_minutes" type="number" min="0" rounded />
            </AtField>
            <AtField :label="$t('Cook (min)')">
                <LogerInput v-model="form.cook_minutes" type="number" min="0" rounded />
            </AtField>
            <AtField :label="$t('Servings')">
                <LogerInput v-model="form.servings" type="number" min="0" rounded />
            </AtField>
        </div>
        <AtField :label="$t('Notes')">
            <textarea
                v-model="form.notes"
                rows="3"
                class="w-full px-4 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:border-primary focus:outline-none"
                :placeholder="$t('Steps, a link to the recipe, tips…')"
            />
        </AtField>
    </div>
</template>
