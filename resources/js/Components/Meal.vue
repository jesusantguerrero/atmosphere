<template>
    <div>
        <div class="py-6 space-y-4">
            <div class="grid grid-cols-4 px-5 py-5 bg-white border rounded-lg cursor-pointer" @click="$inertia.visit(route('meals.edit',meal))" v-for="meal in meals" :key="meal.id">
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
            }
        },
        components: {
            AtBadge,
        },
    }
</script>
