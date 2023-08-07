<script lang="ts" setup>

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import OccurrenceCard from '@/Components/Modules/occurrence/OccurrenceCard.vue';
import CategoryItem from '@/Components/mobile/CategoryItem.vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import MealTypeCell from '@/Components/molecules/MealTypeCell.vue';
import ChoreWidget from '@/Components/widgets/ChoreWidget.vue';

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
      <div
      class="px-5 mx-auto mt-12 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
    >
      <div class="md:w-9/12">
        <WelcomeCard class="mt-5" message="Current home plans">
          <article class="grid grid-cols-2 gap-2 md:flex md:space-x-4">
            <div
              v-for="mealType in []"
              :key="mealType.id"
              class="flex flex-col items-center justify-center w-full h-20 font-bold text-white transition rounded-md cursor-pointer border-primary bg-primary/80"
            >
              <h4 class="capitalize">
                {{ mealType.name }}
              </h4>
              <p>{{ mealType.description }}</p>
            </div>
          </article>
        </WelcomeCard>

        <div class="mt-4 space-y-4">
          <ChoppingListForm :ingredients="ingredients">
            <template #prepend>
              <div class="px-4 py-2 font-bold rounded-md bg-primary/10 text-primary">
                This are the things you'll need this week according to your planning
              </div>
            </template>
          </ChoppingListForm>
        </div>
      </div>
      <div class="py-6 space-y-4 md:w-3/12">
        <OccurrenceCard :checks="checks" />
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
                :label="`Add ${mealType.name}`"
              />
            </section>
          </div>
        </ChoreWidget>
      </div>
    </div>
    </AppLayout>
</template>
