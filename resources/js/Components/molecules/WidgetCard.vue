<template>
  <section
    class="space-y-2 border border-transparent rounded bg-base-lvl-3 p-4 overflow-hidden"
    :class="cardShadow"
  >
    <header class="flex justify-between items-center flex-wrap">
        <SectionTitle>
            <slot name="title">
                {{ title }}
            </slot>
        </SectionTitle>
        <section class="text-secondary font-bold text-sm">
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
        <h4 class="text-secondary font-bold text-4xl relative">
          <slot>{{ value }}</slot>
        </h4>
        <p class="text-body-1/80">
          <slot name="valueDescription">{{ valueDescription }}</slot>
        </p>
      </section>
    </article>
  </section>
</template>

<script setup>
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
    type: String,
  },
});
</script>
