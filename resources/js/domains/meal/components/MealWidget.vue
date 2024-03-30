<script lang="ts" setup>
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import RandomMealCard from '@/Components/widgets/RandomMealCard.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    meals: Record<string, any>[]
}>()

// @ts-ignore: expected in global
const defaultShadow = window?.cardShadow ?? "";
</script>

<template>
    <div  :class="defaultShadow">
        <RandomMealCard class="border-b rounded-b-none" />
        <div class="px-4 py-2 space-y-2 cursor-pointer rounded-b-md min-h-min bg-base-lvl-3">
            <div class="text-center">
                <SectionTitle type="secondary"> {{ $t('Menu for today') }}</SectionTitle>
            </div>
            <slot v-if="meals.length">
                <div v-for="plannedMeal in meals" :key="plannedMeal.id">
                    <h4 class="font-bold capitalize text-body-1">
                        {{ plannedMeal.name }}
                    </h4>
                    <small class="capitalize text-primary">{{ plannedMeal.mealTypeName }}</small>
                </div>
            </slot>
            <div v-else class="py-1.5 text-center">

                <h4 class="py-1 text-2xl font-bold text-body-1"> {{ $t('No meals') }} </h4>
                <Link  class="mx-auto text-primary" href="/planner">{{ $t('Go to planner')}}</Link>
            </div>
        </div>
    </div>
</template>
