<script setup lang="ts">
import { AtButton } from "atmosphere-ui";
import { computed , toRefs } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";

import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import NumberHider from "@/Components/molecules/NumberHider.vue";

import formatMoney from "@/utils/formatMoney";
import { useNetWorth , INetWorthEntry} from "../useNetWorth";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";

const props = withDefaults(defineProps<{
    username: string;
    netWorth: INetWorthEntry[];
    expenses: number | string;
    message: string
}>(), {
  message:  "Welcome to Loger",
});

const { t } = useI18n();

const { netWorth } = toRefs(props)
const { lastMonth, thisMonth, monthMovement, monthMovementVariance } = useNetWorth(netWorth);

const sections = computed(() => ({
  expenses: {
    label: t("Current Expenses"),
    value: props.expenses,
  },
  budget: {
    label: t("Net-Worth Balance"),
    value: thisMonth.value
  },
}));

const isMultiple = (value: any) => {
  return Array.isArray(value);
};
</script>

<template>
  <article
    class="px-5 py-3 transition border divide-y rounded-lg divide-base border-base bg-base-lvl-3"
    :class="[cardShadow]"
  >
    <header class="items-center pb-2 md:justify-between md:flex">
      <h1 class="font-bold text-body">
        {{ message }} <span class="text-primary">{{ username }}</span>
      </h1>
      <div class="space-x-2">
        <AtButton
          class="text-sm text-primary"
          rounded
          @click="router.visit('/finance/transactions')"
        >
          <i class="fa fa-wallet"></i>
          Transactions
        </AtButton>
      </div>
    </header>
    <main class="flex py-3">
      <div
        class="w-full transition cursor-pointer hover:opacity-75"
        @click="$emit('section-click', sectionName)"
        :key="sectionName"
        v-for="(section, sectionName) in sections"
      >
        <h4 class="text-body">{{ section.label }}</h4>
        <SectionTitle
          class="flex flex-col mt-2 space-y-2"
          v-if="isMultiple(section.value)"
        >
          <span class="relative w-72" v-for="currency in section.value" :key="currency">
            <NumberHider />
            {{ formatMoney(currency.total, currency.currency_code) }}
          </span>
        </SectionTitle>
        <SectionTitle class="mt-2" v-else>
          <span class="relative w-72">
            <MoneyPresenter :value="section.value" />
          </span>
        </SectionTitle>
      </div>
    </main>
    <slot />
  </article>
</template>
