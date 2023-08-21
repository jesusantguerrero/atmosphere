<script setup lang="ts">
import SectionTitle from "@/Components/atoms/SectionTitle.vue";

defineProps({
  title: {
    type: String,
  },
  subtitle: {
    type: String,
  },
  leftTitle: {
    type: String,
  },
  value: {
    type: [String, Number],
  },
  valueDescription: {
    type: [String, Number],
  },
});
</script>

<template>
  <section
    class="p-4 space-y-2 overflow-hidden border border-transparent rounded bg-base-lvl-3"
    :class="cardShadow"
  >
    <header class="flex flex-wrap items-center justify-between">
        <SectionTitle>
            <slot name="title">
                {{ title }}
            </slot>
        </SectionTitle>
        <section class="text-sm font-bold text-secondary">
            <slot name="subtitle">
            {{ subtitle }}
            </slot>
        </section>
    </header>
    <article class="flex">
      <div>
        <slot name="leftTitle">
          {{ leftTitle }}
        </slot>
      </div>
      <section>
        <h4 class="relative text-4xl font-bold text-secondary">
          <slot>{{ value }}</slot>
        </h4>
        <p class="text-body-1/80">
          <slot name="valueDescription">{{ valueDescription }}</slot>
        </p>
      </section>
    </article>
  </section>
</template>


