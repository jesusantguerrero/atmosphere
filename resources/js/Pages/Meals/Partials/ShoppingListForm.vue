
<script setup lang="ts">
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps<{
    ingredients: any[];
    shoppingList: Object
}>()


const form = useForm({
    name: '',
    start_at:'',
    end_ad: '',
    items: [

    ]
});

if (props.ingredients) {
    form.items = Object.entries(props.ingredients).map(([itemName, item]) => ({
        ...item,
        id: null,
        name: itemName,
        ingredient_id: item.id
    }))
}


const addToShoppingList = () => {
    form.put('/shopping-list')
}
</script>


<template>
    <section class="rounded-md bg-base-lvl-3 pb-10">
        <header class="px-4 py-4">
            <slot name="prepend">
                    <div class="px-4 py-2 font-bold rounded-md bg-primary/10 text-primary">
                        This are the things you'll need this week according to your planning
                    </div>
            </slot>
        </header>
        <div class="py-5 overflow-hidden divide-base-lvl-2  divide-y-2">
            <section v-for="(ingredient) in form.items" :key="ingredient.id" class="px-5 py-2 justify-between flex capitalize cursor-pointer text-body-1/80">
                <section class="flex items-center space-x-2 w-full">
                    <IFluentFoodApple20Filled />
                    <span class="font-bold">
                        {{ ingredient.name }}
                    </span>
                </section>

                <section class="text-left w-2/12">
                    <span class="font-bold">
                        Qty:
                    </span>
                    <span class="font-bold text-primary">
                        {{ ingredient.quantity }} {{ ingredient.unit }}
                    </span>
                </section>
                <section class="flex items-center space-x-2 w-2/12  justify-end">
                    <div class="">
                        <button
                            class=" px-3 py-3 rounded-l-md dark:bg-base-lvl-1 bg-gray-200 transition-colors"
                            :class="{'hover:bg-green-400 dark:hover:bg-green-400 hover:text-white': !disabled }"
                            @click.stop="ingredient.quantity+=1">
                                <IMdiChevronUp />
                        </button>
                        <button
                            class="px-3 py-3 rounded-r-md dark:bg-base-lvl-1 bg-gray-200 transition-colors"
                            :class="{'hover:bg-red-400 dark:hover:bg-red-400 hover:text-white': !disabled}"
                            @click.stop="ingredient.quantity-=1">
                                <IMdiChevronDown />
                        </button>
                    </div>
                </section>
            </section>
        </div>
        <footer class="w-full flex px-4">
            <LogerButton
                v-if="shoppingList" @click="addToShoppingList"
                class="ml-auto"
                variant="secondary"
                :processing="form.processing"
            >
                Add to shopping list
            </LogerButton>
            <LogerButton class="ml-auto" variant="secondary" v-else>
                Create shopping list
            </LogerButton>
        </footer>
    </section>
</template>
