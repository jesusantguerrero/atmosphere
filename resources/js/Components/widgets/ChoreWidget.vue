<script lang="ts" setup>
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import RandomMealCard from '@/Components/widgets/RandomMealCard.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

defineProps<{
    chores: Record<string, string>[]
}>()
</script>

<template>
    <div class="pb-10"  :class="cardShadow">
        <RandomMealCard class="rounded-b-none border-b mt-4" title="Boards" />
        <div class="px-4 py-2 space-y-4 rounded-b-md cursor-pointer min-h-min bg-base-lvl-3">
            <div class="text-center">
                <SectionTitle type="secondary"> {{ $t('Chores for today') }}</SectionTitle>
            </div>
            <slot v-if="chores.length">
                <div v-for="plannedMeal in meals" :key="plannedMeal.id">
                    <h4 class="font-bold capitalize text-body-1">
                        {{ plannedMeal.name }}
                    </h4>
                    <small class="capitalize text-primary">{{ plannedMeal.mealTypeName }}</small>
                </div>
            </slot>
            <div v-else class="py-1.5 text-center flex-col items-center justify-center">

                <h4 class="py-1 text-2xl font-bold text-body-1"> {{$t('No chores') }} </h4>
                <footer class="w-full mt-4">
                    <LogerButton variant="inverse" class="mx-auto">
                        {{ $t('Go to planner')}}
                    </LogerButton>
                </footer>
            </div>
        </div>
    </div>
</template>
