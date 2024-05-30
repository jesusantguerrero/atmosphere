<template>
    <section class="rounded-md bg-base-lvl-3 pb-10">
        <header class="px-4 py-4">
            <slot name="prepend" />
        </header>
        <div class="py-5 overflow-hidden divide-base-lvl-2  divide-y-2">
            <section v-for="(ingredient) in form.items" :key="ingredient.id" class="px-5 py-2 justify-between flex capitalize cursor-pointer text-body-1/80 font-bold">
                <section class="flex items-center space-x-2">
                    <IFluentFoodApple20Filled />
                    <span>
                        {{ ingredient.name }}
                    </span>
                </section>

                <section class="flex items-center space-x-2">
                    <span>
                        ({{ ingredient.quantity }}) {{ ingredient.unit }}
                    </span>
                    <div class="flex h-8 overflow-hidden border rounded-lg border-primary">
                        <Button
                            class="flex items-center p-4 text-white bg-primary"
                            @click="ingredient.quantity+=1">
                            <IMdiPlus />
                        </Button>
                        <Button class="flex items-center p-4 text-primary "
                        @click="ingredient.quantity-=1"
                        >
                            <IMdiMinus />
                        </Button>
                    </div>
                </section>
            </section>
        </div>
        <footer class="w-full flex px-4">
            <LogerButton class="ml-auto" variant="secondary">
            Create chopping list
            </LogerButton>
        </footer>
    </section>
</template>


<script setup>
import LogerButton from '@/Components/atoms/LogerButton.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    ingredients: {
        type: Array
    }
})


const form = useForm({
    name: '',
    start_at:'',
    end_ad: '',
    items: [

    ]
});

if (props.ingredients) {
    console.log(typeof props.ingredients)
    form.items = Object.entries(props.ingredients).map(([itemName, item]) => ({
        ...item,
        id: null,
        name: itemName,
        ingredient_id: item.id
    }))
}
</script>
