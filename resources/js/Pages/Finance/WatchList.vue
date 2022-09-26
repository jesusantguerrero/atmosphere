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
          <SectionTitle type="secondary"> {{ formatMonth(pageState.dates.startDate) }} </SectionTitle>
        </header>

        <section class="grid grid-cols-3 gap-4 mt-4">
          <WatchedItem
            v-for="watchedItem in data"
            :key="watchedItem.name"
            :item="watchedItem"
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

<script setup>
import { ref, toRefs } from "vue";
import { AtButton, AtDatePager } from "atmosphere-ui";

import AppLayout from "@/Layouts/AppLayout.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import WatchedItem from "@/Components/WatchedItem.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WatchlistModal from "@/Components/WatchlistModal.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMonth } from "@/utils";

const { serverSearchOptions } = toRefs(props);
const { state: pageState } = useServerSearch(serverSearchOptions);

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

const isModalOpen = ref(false);
const resourceToEdit = ref(null);
</script>
