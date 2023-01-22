<template>
  <section class="relative px-8 pt-16 pb-20 mx-auto max-w-screen-2xl">
    <header class="" v-if="showMealTypes && pageProps.mealTypes">
      <article class="flex w-full justify-between mb-2">
        <SectionTitle> Meals </SectionTitle>
      </article>
      <article class="grid grid-cols-2 gap-2 md:flex md:space-x-4">
        <div
          v-for="mealType in pageProps.mealTypes"
          :key="mealType.id"
          class="cursor-pointer font-bold text-white border-primary transition rounded-md bg-primary/80 h-20 w-full flex flex-col items-center justify-center"
        >
          <h4 class="capitalize">
            {{ mealType.name }}
          </h4>
          <p>{{ mealType.description }}</p>
        </div>
      </article>
    </header>

    <div class="w-full mt-4">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { provide, reactive, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import TransactionModal from "@/Components/TransactionModal.vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import ImportResourceModal from "@/Components/ImportResourceModal.vue";

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
