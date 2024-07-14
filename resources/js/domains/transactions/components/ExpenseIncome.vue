<script lang="ts" setup>
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";

interface IFooterStat {
    label: string;
    value: number;
    class?: string;
}

withDefaults(defineProps<{
    title?: string;
    subtitle?: string;
    value: number;
    footerStats?: IFooterStat[];
    footer?: string
}>(), {
    title: 'Month Revenue',
})
</script>


<template>
  <section class="p-4 space-y-2 border border-transparent rounded bg-base-lvl-3" :class="[cardShadow]">
    <header class="flex items-center justify-between">
      <SectionTitle>{{ title }}</SectionTitle>
      <span class="text-sm font-bold text-secondary">
        {{  subtitle }}
    </span>
    </header>
    <article class="flex space-x-4">
      <section>
        <h4 class="relative text-3xl font-bold text-secondary">
            <MoneyPresenter :value="value" />
        </h4>
        <p class="mt-4 space-x-4 text-body-1/80">
            <span v-for="stat in footerStats">
                {{ stat.label}}:
                <MoneyPresenter
                    :class="stat.class"
                    :value="stat.value"
                />
            </span>
        </p>
        <p class="mt-0 text-body-1"> {{ footer }}</p>
      </section>
    </article>
  </section>
</template>

