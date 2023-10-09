<script setup lang="ts">
import { ref, toRefs, computed } from "vue";
import { router } from "@inertiajs/vue3";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";

import WatchlistCard from "@/domains/watchlist/components/WatchlistCard.vue";
import WatchlistModal from "@/domains/watchlist/components/WatchlistModal.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMonth, MonthTypeFormat } from "@/utils";

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  data: {
    type: Array,
    default() {
      return [];
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

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true,
    defaultDates: true,
});


const sectionTitle = computed(() => {
    return "Spending watchlist for " + formatMonth(pageState.dates.startDate, MonthTypeFormat.long);
})
const isModalOpen = ref(false);
const resourceToEdit = ref(null);
</script>

<template>
  <AppLayout :title="sectionTitle">
    <template #header>
      <FinanceSectionNav>
        <template #actions>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            @change="executeSearchWithDelay(5)"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          >
            {{ formatMonth(pageState.dates.startDate, "MMMM") }}
          </AtDatePager>
          <div>
            <LogerButton variant="inverse" @click="isModalOpen=!isModalOpen"> Add WatchList </LogerButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      <article class="w-full">
        <section class="grid gap-4 mt-4 lg:grid-cols-3">
          <WatchlistCard
            v-for="item in data"
            :key="item.name"
            :item="item"
            class="cursor-pointer"
            @click="router.visit(route('watchlist.show', item))"
          />
        </section>
      </article>

      <WatchlistModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
      />
    </FinanceTemplate>
  </AppLayout>
</template>


