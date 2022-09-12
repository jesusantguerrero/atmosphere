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
            <AtButton class="w-48 text-white rounded-md bg-primary">
                Add WatchList
            </AtButton>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
      <section class="w-full">
        <header class="mt-5">
            <SectionTitle type="secondary">May </SectionTitle>
        </header>

        <section class="grid grid-cols-2 gap-4 mt-4">
            <WatchedItem v-for="watchedItem in data" :key="watchedItem.name" :item="watchedItem" />
        </section>
      </section>
    </FinanceTemplate>
  </AppLayout>
</template>

<script setup>
import { toRefs } from "vue";
import { AtButton, AtDatePager } from "atmosphere-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import FinanceTemplate from "@/Components/templates/FinanceTemplate.vue";
import FinanceSectionNav from "@/Components/templates/FinanceSectionNav.vue";
import { useServerSearch } from "./useServerSearch";
import WatchedItem from "./watchedItem.vue";

const { serverSearchOptions } = toRefs(props);
const {state: pageState} = useServerSearch(serverSearchOptions);

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  data: {
    type: Array,
    default() {
      return [];
    }
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

</script>
