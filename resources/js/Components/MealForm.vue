<template>
    <div>
        <at-field
            label="Title"
        >
            <at-input v-model="form.name"></at-input>
        </at-field>

        <at-field
            label="Ingredients"
        >
            <div class="flex px-2 py-2 bg-white" v-for="(ingredient, index) in form.ingredients" :key="ingredient.id">
                <div class="px-4">
                    <span>Qty</span>
                    <at-input type="number" v-model="ingredient.quantity" class="bg-gray-100 border-none rounded-t-none rounded-b-none"></at-input>
                </div>
                <div class="w-full px-4">
                    <span>Name</span>
                    <at-input class="bg-gray-100 border-none rounded-t-none rounded-b-none" v-model="ingredient.name" @update:modelValue="checkIngredients(index, $event)"></at-input>
                </div>
                <div class="px-4">
                    <span>Unit</span>
                    <at-input v-model="ingredient.unit" class="bg-gray-100 border-none rounded-t-none rounded-b-none"></at-input>
                </div>
                <div>
                    <span class="inline-block">Actions</span>
                    <div>
                        <at-button type="danger" @click="removeIngredient(index)">
                            <i class="fa fa-trash"></i>
                        </at-button>
                    </div>
                </div>
            </div>
        </at-field>
    </div>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3"
    import { AtField, AtInput, AtButton } from "atmosphere-ui"
    import { nextTick } from '@vue/runtime-core'

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
        ...props.meal,
        ingredients: props.meal ? [...props.meal?.ingredients, {}]: [{}]
    })


    const submit = async () => {
        const method = props.meal ? 'put' : 'post';
        const url = props.meal ? `/meals/${props.meal.id}` : '/meals';
        form
            .transform(data => ({
                ...data,
                ingredients: data.ingredients.filter( ingredient => ingredient.name)
        }))
        .submit(method, url, {
            onSuccess: async () => {
                if (method == 'post') {
                    await nextTick()
                    form.reset()
                }
            }
        })

    }

    const checkIngredients = (index, value) => {
        if (form.ingredients.length == index + 1 && value) {
            form.ingredients.push({
                unit: 'unit',
                quantity: 1,
                name: ''
            })
        }
    }

    const removeIngredient = (index) => {
        form.ingredients.splice(index, 1);
        const len = form.ingredients.length - 1;
        nextTick(() => {
            checkIngredients(len, form.ingredients[len].name);
        })
    }

    defineExpose({
        submit,
    })
</script>
