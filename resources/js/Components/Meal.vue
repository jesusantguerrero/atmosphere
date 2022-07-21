<template>
    <div>
        <div class="py-6 space-y-4">
            <div
                class="flex justify-between w-full grid-cols-4 px-5 py-5 text-gray-200 border rounded-lg cursor-pointer bg-base-600 hover:bg-base-500"
                @click="$emit('click', meal)"
                v-for="meal in meals"
                :key="meal.id"
                :class="{'bg-primary-300 text-white': isSelected(meal)}"
            >
                <div > {{ meal.name }} </div>
                <div class="space-x-2 text-right">
                    <AtBadge type="primary"> Available </AtBadge>
                    <AtButton type="secondary">
                        <i class="fa fa-trash"></i>
                    </AtButton>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { AtBadge } from "atmosphere-ui";

    export default {
        props: {
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
        },
        components: {
            AtBadge,
        },

        setup(props) {

            return {
                isSelected(meal) {
                    return props.selected.map(selectedMeal => selectedMeal.id).includes(meal.id)
                }
            }
        }
    }
</script>
