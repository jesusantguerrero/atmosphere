<script setup lang="ts">
import { usePage } from "@inertiajs/vue3";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";

const pageProps = usePage().props;

defineProps({
  title: {
    type: String,
  },
  showMealTypes: {
    type: Boolean,
    default: true
  }
});
</script>


<template>
  <section class="relative px-8 pt-16 pb-20 mx-auto max-w-screen-2xl">
    <header class="" v-if="showMealTypes && pageProps.mealTypes">
      <article class="flex justify-between w-full mb-2">
        <SectionTitle> {{ $t('Meals') }} </SectionTitle>
      </article>
      <article class="grid grid-cols-2 gap-2 md:flex md:space-x-4">
        <div
          v-for="mealType in pageProps.mealTypes"
          :key="mealType.id"
          class="flex flex-col items-center justify-center w-full h-14 md:h-20 font-bold text-white transition rounded-md cursor-pointer border-primary bg-primary/80"
        >
          <h4 class="capitalize">
            {{ $t(mealType.name) }}
          </h4>
          <!-- Only show a description when it's a real note, not an echo of the
               type name (raw or translated) — that echo was the "Breakfast
               Breakfast" duplicate. -->
          <p
            v-if="mealType.description
              && mealType.description.trim().toLowerCase() !== mealType.name.trim().toLowerCase()
              && mealType.description.trim().toLowerCase() !== $t(mealType.name).trim().toLowerCase()"
          >{{ mealType.description }}</p>
        </div>
      </article>
    </header>

    <div class="w-full mt-4">
      <slot />
    </div>
  </section>
</template>
