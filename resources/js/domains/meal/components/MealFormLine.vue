<script setup lang="ts">
    import { AtField, AtButton } from "atmosphere-ui"

    import LogerInput from "@/Components/atoms/LogerInput.vue";
    import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
    import { IMealIngredient } from "../models";

    defineEmits(['close']);

    withDefaults(defineProps<{
        meal: Object;
        ingredient: IMealIngredient;
        index: number;
        viewOnly: boolean
    }>(), {
        meal: () => ({
            name: '',
        })
    });
</script>

<template>
    <article class="flex px-2 py-2 overflow-hidden rounded-md bg-base-lvl-3" v-if="viewOnly">
        <section>
            <h4 class="text-lg first-letter:capitalize font-bold">{{ ingredient.name }}</h4>
            <p><span>{{ ingredient.quantity }} {{ ingredient.unit}}</span></p>
        </section>
    </article>

    <div class="flex px-2 py-2 overflow-hidden rounded-md bg-base-lvl-3" v-else>
        <AtField class="px-4" label="Qty">
            <LogerInput bordered rounded type="number" v-model="ingredient.quantity" />
        </AtField>
        <AtField class="w-full px-4" label="Name">
            <div v-if="ingredient.product_id" class="capitalize">
                {{ ingredient.name }}
            </div>
            <LogerApiSimpleSelect
                v-else
                v-model="ingredient.product_id"
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


