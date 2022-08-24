<template>
  <section class="pl-6 pb-20 pt-16 max-w-screen-2xl">
    <header class="" v-if="showMealTypes">
      <article class="flex w-full justify-between mb-2">
        <SectionTitle> Meals </SectionTitle>
        <LogerTabButton class="font-bold">Add Meal</LogerTabButton>
      </article>
      <article class="flex space-x-4">
        <div
          v-for="mealType in pageProps.mealTypes"
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

    <ImportResourceModal v-model:show="isImportModalOpen" />
  </section>
</template>

<script setup lang="ts">
import { provide, reactive, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import TransactionModal from "@/Components/TransactionModal.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
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
