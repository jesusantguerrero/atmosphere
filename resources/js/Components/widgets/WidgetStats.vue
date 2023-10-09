<script setup lang="ts">
import { Link } from "@inertiajs/vue3";

interface StatCard {
  label: string;
  value: string | number;
}

interface CardAction {
    link: string,
    label: string,
}

defineProps<{
  total: number;
  description: string;
  cards: StatCard[];
  action?: CardAction;
}>();
</script>

<template>
  <article class="bg-base-lvl-3 border py-2 px-4">
    <header class="flex justify-between">
      <section class="flex items-center">
        <slot name="icon">
            <IMaterialSymbolsHomeWorkOutline class="text-5xl text-secondary" />
        </slot>
        <section class="ml-3">
          <h4 class="text-primary font-extrabold text-2xl ">{{ total }}</h4>
          <p class="text-body-1">{{ description }}</p>
        </section>
      </section>
      <section v-if="action">
        <Link :href="action.link" class="flex items-center text-primary">
          {{ action.label }}
          <IMdiChevronRight />
        </Link>
      </section>
    </header>
    <main class="flex mt-4 bg-secondary/5 rounded-md py-2 mb-4 divide-x">
      <article v-for="stat in cards" class="w-full text-center capitalize ">
        <h4 class="font-bold text-secondary">{{ stat.value }}</h4>
        <p class="text-body-1">{{ stat.label }}</p>
      </article>
    </main>
  </article>
</template>
