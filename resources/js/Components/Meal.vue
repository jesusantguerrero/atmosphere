<template>
    <div>
        <div class="py-6 space-y-4">
            <div
                class="flex justify-between w-full grid-cols-4 px-5 py-5 border rounded-lg cursor-pointer text-body bg-base-lvl-3 hover:bg-base-lvl-2"
                @click="$emit('click', meal)"
                v-for="meal in meals"
                :key="meal.id"
                :class="{'bg-primary-300 text-white': isSelected(meal)}"
            >
                <div > {{ meal.name }} </div>
                <div class="flex items-center space-x-2 text-right">
                    <div class="flex items-center space-x-1 font-bold">
                        <div v-for="tag in meal.labels" :style="{color: tag.color}">
                            #{{ tag.name  }}
                        </div>
                        <LogerApiSelect
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
                    <AtBadge type="primary"> Available </AtBadge>
                    <AtButton type="secondary">
                        <i class="fa fa-trash"></i>
                    </AtButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { AtBadge } from "atmosphere-ui";
    import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";
    import { reactive } from "vue";

    const props = defineProps({
        meals: {
            type: Array,
            required: true
        },
        selected: {
            type: Array,
            default() {
                return []
            }
        }
    });

    const label = reactive({
        label_id: "",
        name: ""
    })

    const isSelected = (meal) => {
        return props.selected.map(selectedMeal => selectedMeal.id).includes(meal.id)
    }
</script>
