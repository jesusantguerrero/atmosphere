<script setup lang="ts">
import { reactive, computed, toRefs, ref, capitalize, watch } from "vue";
import { router } from "@inertiajs/vue3";

import { formatMoney } from "@/utils";
import AppLayout from "@/Components/templates/AppLayout.vue";
import { useServerSearch } from "@/composables/useServerSearchV2";
import { usePrint } from "@/utils/usePrint";

const props = defineProps({
  categoryType: {
    type: String,
    default: "income",
  },
  categories: {
    type: Array,
  },
  accounts: {
    type: Array,
  },
  serverSearchOptions: {
    type: Object,
    default() {
      return {};
    },
  },
});

const sectionTitle = computed(() => {
  return `${capitalize(props.categoryType)} - Statement`;
});

const state = reactive({
  isSummary: true,
  isTransferModalOpen: false,
  ledger: computed(() => {
    return props.categories.ledgers;
  }),
  categoriesTotal: computed(() => {
    return Object.values(props.categories.ledgers).reduce((total, category) => {
      return total + parseFloat(category?.total || 0);
    }, 0);
  }),
  transferAccount: null,
});

const lastParent = ref(null);
const hasHeader = (category: Record<string, any>) => {
  const parent = category.category;
  if (parent && (!lastParent.value || lastParent.value.id !== parent.id)) {
    lastParent.value = parent;
    return true;
  } else {
    return false;
  }
};

const { isSummary, ledger, categoriesTotal } = toRefs(state);

const { serverSearchOptions } = toRefs(props);
const { executeSearch, state: searchState } = useServerSearch(serverSearchOptions, {
  manual: false,
  defaultDates: false,
});
const filters = reactive({
  property: null,
  account: null,
});

const { customPrint } = usePrint("report");
</script>

<template>
  <AppLayout :title="sectionTitle">
    <template #header>
      <div class="flex items-center justify-end py-1 mx-5 rounded-md">
        <div class="flex space-x-2 font-bold text-gray-500 rounded-t-lg max-w-min">
          <ElDatePicker
            :model-value="[searchState.dates.startDate, searchState.dates.endDate]"
            type="daterange"
            unlink-panels
            range-separator="To"
            start-placeholder="Start date"
            end-placeholder="End date"
            size="large"
            @update:model-value="
              (event) => {
                searchState.dates.startDate = event[0];
                searchState.dates.endDate = event[1];
              }
            "
          />
          <BaseSelect
            endpoint="/api/properties"
            v-model="filters.property"
            @update:model-value="searchState.filters.property = $event.id"
            label="name"
            track-by="id"
            class="md:w-[200px]"
            placeholder="Propiedad o Dueño"
          />
          <AccountSelect
            endpoint="/api/accounts"
            v-model="filters.account"
            @update:mode-value="searchState.filters.account = $event.id"
            class="md:w-[200px]"
            multiple
          />
          <AppButton @click="executeSearch()"> Generar Reporte </AppButton>
          <AppButton variant="neutral" @click="customPrint()">
            <IMdiPrinter />
          </AppButton>
        </div>
      </div>
    </template>

    <div
      class="w-full py-10 mx-auto mt-16 mb-32 bg-white rounded-md shadow-md printable sm:px-6 lg:px-8 print:shadow-none print:w-screen print:absolute print:mt-0"
      id="report"
    >
      <header class="text-center text-gray-500">
        <h4 class="text-3xl font-bold capitalize">{{ sectionTitle }}</h4>
        <h5 class="font-bold">Neatforms</h5>
        <p>From date to date</p>
      </header>

      <div class="flex items-center justify-end space-x-2 print:hidden">
        <section class="flex space-x-2">
          <AppButton variant="secondary" @click="isSummary = true"> Summary </AppButton>
          <AppButton variant="secondary" @click="isSummary = false"> Details </AppButton>
        </section>
      </div>
      <div class="mt-10 items" :class="{ 'divide-y': isSummary }">
        <div v-for="group in ledger" :key="group.id" class="py-2">
          <div
            v-if="hasHeader(group)"
            class="w-full px-5 py-2 mt-5 font-bold bg-gray-200"
          >
            {{ group.alias ?? group.name }}
            {{ group?.total }}
          </div>
          <div class="divide-y" v-if="!isSummary">
            <div class="px-5 py-2 font-semibold bg-gray-100">
              {{ group.alias ?? group.name }}
            </div>
            <article class="w-full px-5" v-for="categories in group.categories">
              <header class="flex justify-between py-2">
                <span class="font-semibold text-blue-500">
                  {{ categories.alias ?? categories.name }}
                </span>
                <div class="space-x-4">
                  <span class="font-bold text-success">
                    {{ formatMoney(categories.income) }}</span
                  >
                  <span class="font-bold text-error">
                    {{ formatMoney(categories.outcome) }}</span
                  >
                  <span> {{ formatMoney(categories?.total) }}</span>
                  <!-- <AtButton @click="setPayment(account)"> Pay </AtButton> -->
                </div>
              </header>
              <section>
                <article class="w-full px-5" v-for="account in categories.accounts">
                  <header class="flex justify-between py-2">
                    <span class="font-semibold text-blue-500">
                      {{ account.alias ?? account.name }}
                    </span>
                    <div class="space-x-4">
                      <span class="font-bold text-success">
                        {{ formatMoney(account.income) }}</span
                      >
                      <span class="font-bold text-error">
                        {{ formatMoney(account.outcome) }}</span
                      >
                      <span> {{ formatMoney(account?.total) }}</span>
                      <!-- <AtButton @click="setPayment(account)"> Pay </AtButton> -->
                    </div>
                  </header>
                </article>
              </section>
            </article>
          </div>
          <div class="flex justify-between px-5 py-2" :class="{ 'border-t': !isSummary }">
            <span class="font-bold"> {{ group.alias ?? group.name }} </span>
            <div class="flex space-x-4">
              <span class="font-bold text-success"> {{ formatMoney(group.income) }}</span>
              <span class="font-bold text-error"> {{ formatMoney(group.outcome) }}</span>
              <span class="font-bold"> {{ formatMoney(group?.total) }}</span>
            </div>
          </div>
        </div>

        <div class="flex justify-between py-5 text-xl capitalize">
          <span class="font-bold"> Total {{ categoryType }}s </span>
          <section class="space-x-2">
            <span v-if="categoryType == 'balance-sheet'">
              {{ formatMoney(ledger.assets?.total) }} =
              {{ formatMoney(ledger.liabilities?.total) }} +
              {{ formatMoney(ledger.equity?.total) }}
            </span>
          </section>
        </div>
      </div>
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
