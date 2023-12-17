<script lang="ts" setup>
import { reactive, computed, watch, toRefs } from "vue";
import { AtButton, AtDatePager, AtBackgroundIconCard } from "atmosphere-ui";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import InvoiceTable from "@/Components/templates/InvoiceTable";
import AccountingSectionNav from "../Partials/AccountingSectionNav.vue";
import { useServerSearch } from "@/utils/useServerSearch";
import { formatMoney } from "@/utils";

const props = defineProps({
  invoices: {
    type: Array,
  },
  type: {
    type: String,
  },
  paid: Number,
  outstanding: Number,
  total: Number,
  serverSearchOptions: Object,
});

const state = reactive({
  sectionName: computed(() => {
    return `${props.type.toLowerCase()}s`;
  }),
});

const filters = reactive({
  client_id: null,
});

watch(
  () => filters,
  () => {
    const selectedFilters = Object.entries(filters).reduce(
      (acc, [filterName, filter]) => {
        acc[filterName] = filter?.id;
        return acc;
      },
      {}
    );
    router.get(
      location.pathname,
      {
        filters: selectedFilters,
      },
      { preserveState: true }
    );
  },
  { deep: true }
);

const { serverSearchOptions } = toRefs(props);
const { executeSearchWithDelay, updateSearch, state: pageState } = useServerSearch(
  serverSearchOptions,
  (urlParams: string) => {
    updateSearch(`${location.pathname}?${urlParams}`);
  },
  {
    manual: true,
  }
);
</script>

<template>
  <AppLayout title="Transaccciones">
    <template #header>
      <AccountingSectionNav>
        <template #actions>
          <p>Total: {{ invoices.data.length }}</p>
          <AtButton
            @click="router.visit(`/${state.sectionName}/create`)"
            variant="inverse"
            >Imprimir</AtButton
          >
          <AtButton
            @click="router.visit(`/${state.sectionName}/create`)"
            variant="inverse"
            >Filtros</AtButton
          >
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            @change="executeSearchWithDelay()"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          />
        </template>
      </AccountingSectionNav>
    </template>

    <div class="py-10 mx-auto sm:px-6 lg:px-8">
      <section class="flex space-x-4">
        <AtBackgroundIconCard
          class="w-full bg-white border h-28 text-body-1"
          :title="$t('Paid')"
          :value="formatMoney(paid ?? 0)"
        />
        <AtBackgroundIconCard
          class="w-full bg-white border h-28 text-body-1"
          :title="$t('Pending balance')"
          :value="formatMoney(outstanding ?? 0)"
        />
        <AtBackgroundIconCard
          class="w-full bg-white border h-28 text-body-1"
          :title="$t('Total')"
          :value="formatMoney(total || 0)"
        />
      </section>
      <InvoiceTable :invoice-data="invoices.data" class="mt-10 bg-base-lvl-3" />
    </div>
  </AppLayout>
</template>

<style lang="scss" scoped>
.body-section {
  background: white;
  padding: 15px;
}

.el-table th {
  font-weight: bolder;
  color: #222 !important;
}

.section-actions {
  display: flex;

  .app-search__container {
    width: 80%;
    margin-right: 15px;
  }

  .action-buttons {
    width: 20%;
    display: flex;

    button {
      margin-left: auto;
    }
  }
}
</style>
