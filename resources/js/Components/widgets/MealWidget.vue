<template>
    <div class="pb-10"  :class="cardShadow">
        <RandomMealCard class="rounded-b-none border-b mt-4" />
        <div class="px-4 py-2 space-y-4 rounded-b-md cursor-pointer min-h-min bg-base-lvl-3">
            <div class="text-center">
                <SectionTitle type="secondary"> {{ t('Menu for today') }}</SectionTitle>
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

                <h4 class="py-1 text-2xl font-bold text-body-1"> {{t('No meals') }} </h4>
                <LogerButton variant="inverse">{{ t('Go to planner')}}</LogerButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import RandomMealCard from '@/Components/widgets/RandomMealCard.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

defineProps({
    meals: {
        type: Array,
        default() {
            return []
        }
    }
})
</script>
