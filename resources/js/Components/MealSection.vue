<template>
    <section class="py-6 space-y-4">
        <article
            class="flex justify-between w-full grid-cols-4 py-2 px-2 shadow-md items-center border rounded-lg cursor-pointer text-body bg-base-lvl-3 hover:bg-base-lvl-2"
            v-for="meal in meals"
            :key="meal.id"
            :class="{'bg-primary-300 text-white': isSelected(meal)}"
        >
            <div class="flex items-center">
                <AtButton class="group-hover:text-red-500" @click="$emit('toggle-like', meal)">
                    <IconHeart v-if="meal.is_liked" />
                    <IconHeartOutline v-else />
                </AtButton>

                <span @click="$emit('click', meal)" class="hover:text-primary transition capitalize">

                    {{ meal.name }}
                </span>
            </div>

            <div class="flex items-center space-x-2 text-right">
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
                <LogerTabButton type="secondary">
                    <i class="fa fa-trash"></i>
                </LogerTabButton>
            </div>
        </article>
    </section>
</template>

<script setup>
    import { AtBadge, AtButton } from "atmosphere-ui";
    import { reactive } from "vue";

    import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";
    import LogerApiSimpleSelect from "./organisms/LogerApiSimpleSelect.vue";
    import LogerTabButton from "./atoms/LogerTabButton.vue";
    import IconHeart from "./icons/IconHeart.vue";
    import IconHeartOutline from "./icons/IconHeartOutline.vue";

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
