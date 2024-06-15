<script setup lang="ts">
    // @ts-ignore
    import { AtButton } from "atmosphere-ui";
    import { reactive } from "vue";
    import { useForm } from "@inertiajs/vue3";
    import {  ElMessageBox } from "element-plus";

    import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";
    import IconHeart from "@/Components/icons/IconHeart.vue";
    import IconHeartOutline from "@/Components/icons/IconHeartOutline.vue";
    import LogerButton from "@/Components/atoms/LogerButton.vue";

    import { Meal } from "../models";


    defineProps<{
        meal: Meal;
        isSelected: boolean;
    }>();

    const label = reactive({
        label_id: "",
        name: ""
    })

    const deleteForm = useForm({
        id: 0
    });

    const deleteResource = async (meal: any) => {
        const isConfirmed = await ElMessageBox.confirm("Are you sure to delete this meal?");
        if (!isConfirmed) return

        deleteForm.delete(route('meals.destroy', { meal }))
    }
</script>

<template>
        <article
            class="flex items-center justify-between w-full grid-cols-4 px-2 py-2 border rounded-lg shadow-md cursor-pointer text-body bg-base-lvl-3 hover:bg-base-lvl-2"
            :class="{'bg-primary-300 text-white': isSelected}"
        >
            <section class="flex items-center">
                <AtButton class="group-hover:text-red-500" @click="$emit('toggle-like', meal)">
                    <IconHeart v-if="meal.is_liked" />
                    <IconHeartOutline v-else />
                </AtButton>

                <span @click="$emit('click', meal)" class="capitalize transition hover:text-primary">
                    {{ meal.name }}
                </span>
            </section>

            <section class="flex items-center space-x-2 text-right">
                <div class="flex items-center space-x-1 font-bold text-gray-500">
                    <div v-for="ingredient in meal.ingredients" :style="{color: ingredient.color}" :key="ingredient.id">
                        {{ ingredient.name  }}
                    </div>
                </div>
                <div class="flex items-center space-x-1 font-bold">
                    <div v-for="tag in meal.labels" :style="{color: tag.color}" :key="tag.id">
                        #{{ tag.name  }}
                    </div>
                    <LogerApiSimpleSelect
                        v-model="label.label_id"
                        v-model:label="label.name"
                        class="w-24"
                        tag
                        custom-label="name"
                        track-id="id"
                        placeholder="Add label"
                        endpoint="/api/labels"
                        @update:label="$emit('tag-selected', label, meal)"
                    />
                </div>
                <LogerButton
                    variant="error"
                    @click="deleteResource(meal)"
                    :disabled="deleteForm.processing"
                    :processing="deleteForm.processing && deleteForm.id==meal.id"
                >
                    <template #icon>
                        <i class="fa fa-trash"></i>
                    </template>
                </LogerButton>
            </section>
        </article>
</template>
