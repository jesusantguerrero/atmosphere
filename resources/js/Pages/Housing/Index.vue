<script lang="ts" setup>
import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import OccurrenceWidget from "@/domains/housing/components/OccurrenceWidget.vue";
import ChoreWidget from '@/Components/widgets/ChoreWidget.vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';

import CategoryItem from '@/domains/transactions/components/CategoryItem.vue';
import MealTypeCell from '@/domains/meal/components/MealTypeCell.vue';

import { IOccurrenceCheck } from "@/domains/housing/models";

defineProps<{
    checks: IOccurrenceCheck
}>()
</script>

<template>
    <AppLayout title="Home Projects">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div>
                          <LogerButton variant="inverse" @click="submit()"> Add Check </LogerButton>
                      </div>
                  </template>
            </HouseSectionNav>
        </template>
        <main
            class="px-5 mx-auto mt-12 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
        >
            <div class="mt-6 md:w-6/12 space-y-4">
                <section class="flex flex-col md:flex-row md:space-x-4">
                    <WelcomeCard  message="Loger profiles" class="w-full order-1">
                        <section class="mt-2 md:mt-0 flex flex-col items-center mx-auto">
                            <h4 class="text-lg font-bold text-body-1"> There are not profiles created</h4>
                            <p class="max-w-lg my-3">
                                Lorem ipsum dolor sit amet consectetur adipiscing elit maecenas.
                            </p>
                        </section>
                    </WelcomeCard>
                </section>
            </div>
            <div class="py-6 space-y-4 md:w-6/12">
                <OccurrenceWidget :checks="checks" />
                <ChoreWidget :chores="[]">
                <div class="mt-2">
                    <div
                    v-for="mealType in [{id: '1'}]"
                    class="w-full"
                    :key="`${mealType.id}-${day}`"
                    >
                    <MealTypeCell
                        v-model="recipe"
                        :planned-meal="[]"
                        :meal-type="mealType"
                    />
                    </div>

                    <SectionTitle type="secondary" class="text-center"> Chores </SectionTitle>
                    <section class="flex mt-4">
                    <CategoryItem
                        wrap
                        v-for="mealType in [{ id: 1, name: 'Hola'}]"
                        :label="`${mealType.name}`"
                    />
                    </section>
                </div>
                </ChoreWidget>
            </div>
        </main>
    </AppLayout>
</template>
