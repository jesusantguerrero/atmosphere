<template>
  <section class="space-y-2 border border-transparent rounded bg-base-lvl-3 p-4" :class="[cardShadow]">
    <header class="flex justify-between items-center">
      <SectionTitle>Month Revenue</SectionTitle>
      <span class="text-secondary font-bold text-sm">Oct</span>
    </header>
    <article class="flex space-x-4">
      <section>
        <h4 class="text-secondary font-bold text-3xl relative">
            <MoneyPresenter
                :value="income + expenses"
            />
        </h4>
        <p class="text-body-1/80 mt-4 space-x-4">
            <span>
                Expenses
                <MoneyPresenter
                    class="text-error"
                    :value="expenses"
                />
            </span>
            <span>
                Income:
                <MoneyPresenter
                    class="text-success"
                    :value="income"
                />
            </span>
        </p>
      </section>
    </article>
  </section>
</template>

<script setup>
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import { formatMoney } from "@/utils";
import { useLocalStorage } from "@vueuse/core";
import { addMinutes, format } from "date-fns";
import { onMounted, reactive, computed } from "vue";
import MoneyPresenter from "../molecules/MoneyPresenter.vue";

const props = defineProps({
    income: {
        type: Number
    },
    expenses: {
        type: Number
    }
})


const image = computed(() => {
  return state.value.weather.length && state.value.weather[0].icon;
});

</script>
