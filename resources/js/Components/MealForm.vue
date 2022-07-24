<template>
    <div class="text-body">
        <AtField
            label="Title"
        >
            <AtInput v-model="form.name" rounded class="border text-body bg-base border-base-deep-1" />
        </AtField>

        <AtField
            label="Ingredients"
        >
            <div class="flex px-2 py-2 overflow-hidden rounded-md bg-base-lvl-1" v-for="(ingredient, index) in form.ingredients" :key="ingredient.id">
                <AtField class="px-4" label="Qty">
                    <AtInput rounded type="number" v-model="ingredient.quantity" class="border border-none rounded-t-none rounded-b-none text-body bg-base border-base-deep-1" />
                </AtField>
                <AtField class="w-full px-4" label="Name">
                    <LogerApiSelect
                        v-model="form.ingredients[index]"
                        v-model:label="ingredient.name"
                        class="w-full"
                        tag
                        custom-label="name"
                        track-id="id"
                        placeholder="Add ingredient"
                        endpoint="/api/ingredients"
                        @update:label="checkIngredients(index, $event)"
                    />
                </AtField>
                <AtField class="px-4" label="Unit">
                    <AtInput rounded v-model="ingredient.unit" class="border border-none rounded-t-none rounded-b-none text-body bg-base border-base-deep-1"/>
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
    import LogerApiSelect from "./organisms/LogerApiSelect.vue";

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
                product_id: "",
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
