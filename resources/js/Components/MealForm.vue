<template>
    <div class="text-gray-200">
        <AtField
            label="Title"
        >
            <AtInput v-model="form.name" rounded class="text-gray-200 border bg-slate-700 border-slate-800" />
        </AtField>

        <AtField
            label="Ingredients"
        >
            <div class="flex px-2 py-2 overflow-hidden rounded-md bg-slate-600" v-for="(ingredient, index) in form.ingredients" :key="ingredient.id">
                <AtField class="px-4" label="Qty">
                    <AtInput rounded type="number" v-model="ingredient.quantity" class="text-gray-200 border border-none rounded-t-none rounded-b-none bg-slate-700 border-slate-800" />
                </AtField>
                <AtField class="w-full px-4" label="Name">
                    <AtInput rounded class="text-gray-200 border border-none rounded-t-none rounded-b-none bg-slate-700 border-slate-800" v-model="ingredient.name" @update:modelValue="checkIngredients(index, $event)"></AtInput>
                </AtField>
                <AtField class="px-4" label="Unit">
                    <AtInput rounded v-model="ingredient.unit" class="text-gray-200 border border-none rounded-t-none rounded-b-none bg-slate-700 border-slate-800"/>
                </AtField>
                <AtField label="Actions">
                    <AtButton type="danger" class="items-center h-10" rounded @click="removeIngredient(index)">
                        <i class="fa fa-trash"></i>
                    </AtButton>
                </AtField>
            </div>
        </AtField>
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
        ...{
            name: props.meal?.name,
        },
        ingredients: props.meal ? [...props.meal.ingredients, {}]: [{}]
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
