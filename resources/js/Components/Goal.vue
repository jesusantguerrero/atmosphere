<template>
    <div>
        <div class="py-6 space-y-4">
            <div
                class="grid w-full grid-cols-4 px-5 py-5 bg-base-600 border rounded-lg cursor-pointer hover:bg-primary-200"
                @click="$emit('click', meal)"
                v-for="meal in meals"
                :key="meal.id"
                :class="{'bg-primary-300 text-white': isSelected(meal)}"
            >
                <div > {{ meal.name }} </div>
                <div > Date </div>
                <div > Price </div>
                <div class="space-x-2 text-right"> <at-badge type="primary"> Available </at-badge>  <at-button type="secondary"><i class="fa fa-trash"></i></at-button></div>
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
