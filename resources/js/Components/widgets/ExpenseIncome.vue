<script lang="ts" setup>
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import MoneyPresenter from "../molecules/MoneyPresenter.vue";

interface IFooterStat {
    label: string;
    value: number;
    class?: string;
}

withDefaults(defineProps<{
    title: string;
    subtitle?: string;
    value: number;
    footerStats?: IFooterStat[];
    footer?: string
}>(), {
    title: 'Month Revenue',
})
</script>


<template>
  <section class="space-y-2 border border-transparent rounded bg-base-lvl-3 p-4" :class="[cardShadow]">
    <header class="flex justify-between items-center">
      <SectionTitle>{{ title }}</SectionTitle>
      <span class="text-secondary font-bold text-sm">
        {{  subtitle }}
    </span>
    </header>
    <article class="flex space-x-4">
      <section>
        <h4 class="text-secondary font-bold text-3xl relative">
            <MoneyPresenter :value="value" />
        </h4>
        <p class="text-body-1/80 mt-4 space-x-4">
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

