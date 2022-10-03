<template>
    <div class="flex px-2 py-2 overflow-hidden rounded-md bg-base-lvl-3">
        <AtField class="px-4" label="Qty">
            <LogerInput bordered rounded type="number" v-model="ingredient.quantity" />
        </AtField>
        <AtField class="w-full px-4" label="Name">
            <LogerApiSimpleSelect
                v-model="ingredient"
                v-model:label="ingredient.name"
                class="w-full"
                tag
                custom-label="name"
                track-id="id"
                placeholder="Add ingredient"
                endpoint="/api/ingredients"
                @update:label="$emit('check', $event)"
            />
        </AtField>
        <AtField class="px-4" label="Unit">
            <LogerInput rounded v-model="ingredient.unit" class="border border-none rounded-t-none rounded-b-none text-body bg-base border-base-deep-1"/>
        </AtField>
        <AtField label="Actions">
            <AtButton type="danger" class="items-center h-10" rounded @click="removeIngredient(index)">
                <i class="fa fa-trash"></i>
            </AtButton>
        </AtField>
    </div>
</template>

<script setup>
    import { useForm } from "@inertiajs/inertia-vue3"
    import { AtField, AtInput, AtButton } from "atmosphere-ui"
    import { nextTick } from '@vue/runtime-core'
    import LogerApiSimpleSelect from "./organisms/LogerApiSimpleSelect.vue";
    import LogerInput from "./atoms/LogerInput.vue";

    defineEmits(['close']);
    const props = defineProps({
        meal: {
            type: Object,
            default: () => ({
                name: '',
            }),
        },
        ingredient: {
            type: Object,
            required: true,
        },
        index: {
            type: Number
        }
    });
</script>
