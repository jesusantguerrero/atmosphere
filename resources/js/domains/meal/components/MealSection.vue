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

    interface Label {
        id: number;
        name: string;
        color: string;
    }

    interface Meal {
        [x: string]: any;
        id: number;
        name: string;
        is_liked: boolean;
        labels: Label[]
    }

    const props = defineProps<{
        meals: Meal[];
        selected: Meal[]
    }>();

    const label = reactive({
        label_id: "",
        name: ""
    })

    const isSelected = (meal: Meal) => {
        return !!props.selected
        ?.find(selectedMeal => selectedMeal.id == meal.id)
    }

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
    <section class="py-6 space-y-4">
        <article
            class="flex items-center justify-between w-full grid-cols-4 px-2 py-2 border rounded-lg shadow-md cursor-pointer text-body bg-base-lvl-3 hover:bg-base-lvl-2"
            v-for="meal in meals"
            :key="meal.id"
            :class="{'bg-primary-300 text-white': isSelected(meal)}"
        >
            <div class="flex items-center">
                <AtButton class="group-hover:text-red-500" @click="$emit('toggle-like', meal)">
                    <IconHeart v-if="meal.is_liked" />
                    <IconHeartOutline v-else />
                </AtButton>

                <span @click="$emit('click', meal)" class="capitalize transition hover:text-primary">
                    {{ meal.name }}
                </span>
            </div>

            <div class="flex items-center space-x-2 text-right">
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
            </div>
        </article>
    </section>
</template>
