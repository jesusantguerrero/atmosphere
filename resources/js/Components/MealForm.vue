<template>

    <div>
        <at-field
            label="Meal Name"
        >
            <at-input v-model="form.name"></at-input>
        </at-field>

        <at-field
            label="Ingredients"
        >
            <div class="grid grid-cols-4 py-2 bg-white" v-for="(ingredient, index) in form.ingredients" :key="ingredient.id">
                <div class="px-4">
                    <span>Qty</span>
                    <at-input type="number" v-model="ingredient.quantity" class="bg-gray-100 border-none rounded-t-none rounded-b-none"></at-input>
                </div>
                <div class="px-4">
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

<script>
    import Modal from '@/Jetstream/Modal'
    import { useForm } from "@inertiajs/inertia-vue3"
    import { AtField, AtInput, AtButton, AtTextarea } from "atmosphere-ui/dist/atmosphere-ui.es"
    import { reactive, toRefs } from '@vue/reactivity'
    import { nextTick } from '@vue/runtime-core'

    export default {
        emits: ['close'],

        components: {
            Modal,
            AtField,
            AtInput,
            AtButton,
            AtTextarea
        },

        props: {
            meal: {
                type: Object,
                required: true
            }
        },
        setup(props, { emit }) {
            const state = reactive({
                form: useForm({
                    ...props.meal,
                    ingredients: [...props.meal.ingredients, {

                    }]
                })
            })


            const submit = () => {
                state.form
                  .transform(data => ({
                        ...data,
                        ingredients: data.ingredients.filter( ingredient => ingredient.name)
                }))
                .put(route('meals.update', props.meal))
            }

            const checkIngredients = (index, value) => {
                if (state.form.ingredients.length == index + 1 && value) {
                    state.form.ingredients.push({
                        unit: 'unit',
                        quantity: 1,
                        name: ''
                    })
                }
            }

            const removeIngredient = (index) => {
                state.form.ingredients.splice(index, 1);
                const len = state.form.ingredients.length - 1;
                nextTick(() => {
                    checkIngredients(len, state.form.ingredients[len].name);
                })
            }

            return {
                ...toRefs(state),
                submit,
                checkIngredients,
                removeIngredient
            }
        }
    }
</script>
