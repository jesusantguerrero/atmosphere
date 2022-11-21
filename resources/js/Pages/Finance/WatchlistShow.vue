<template>
  <AppLayout title="Spending Watchlist">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          />
          <div>
            <LogerButton variant="inverse" @click="isModalOpen=!isModalOpen"> Add WatchList </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      <article class="w-full">
        <header class="mt-5">
          <SectionTitle type="secondary"> {{ resource.name }} : {{ formatMonth(pageState.dates.startDate, MonthTypeFormat.long) }} </SectionTitle>
        </header>

        <section class="mt-4">
          <header>
            <h4 class="text-4xl font-bold">{{ formatMoney(50000) }}</h4>
          </header>
          <section class="py-8">
            <ChartComparison
                class="w-full mt-4 mb-10 overflow-hidden bg-white rounded-lg shadow-xl"
                :title="`${resource.name} Report`"
                ref="ComparisonRevenue"
                :data="{}"
            />
          </section>
        </section>
      </article>

      <WatchlistModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
      />

      <template #panel>
        <section class="fixed">
            <SectionTitle class="flex items-center justify-between pl-5 mt-5" type="secondary">
                <span>
                    Transactions
                </span>
                <LogerTabButton class="flex items-center ml-2 text-primary" @click="isImportModalOpen=!isImportModalOpen" title="import">
                    {{ formatMoney(budgetAccountsTotal) }}
                    <IconImport />
                </LogerTabButton>
            </SectionTitle>
        </section>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { ref, toRefs } from "vue";
import { AtButton, AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WatchlistCard from "@/Components/WatchlistCard.vue";
import WatchlistModal from "@/Components/WatchlistModal.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMoney, formatMonth, MonthTypeFormat } from "@/utils";
import IconImport from "@/Components/icons/IconImport.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import ChartComparison from "@/Components/widgets//ChartComparison.vue";

const { serverSearchOptions } = toRefs(props);
const { state: pageState } = useServerSearch(serverSearchOptions);

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  resource: {
    type: Object,
    default() {
      return {};
    },
  },
  categories: {
    type: Array,
    default() {
      return [];
    },
  },
  accounts: {
    type: Array,
    default() {
      return [];
    },
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
});

const isModalOpen = ref(false);
const resourceToEdit = ref(null);
</script>
