<script setup lang="ts">
// @ts-ignore
import { AtButton, AtBackgroundIconCard, AtDatePager } from "atmosphere-ui";
import AppLayout from "@/Components/templates/AppLayout.vue";
import { toRefs, ref } from "vue";

import cols from "./cols";
import AtTable from "@/Components/shared/BaseTable.vue";
import { formatMoney, formatDate } from "@/utils";
import { useServerSearch } from "@/utils/useServerSearch";
import AccountSelect from "@/Components/shared/Selects/AccountSelect.vue";
import { IPayment } from "@/Modules/loans/loanEntity";
import { IServerSearchData } from "@/utils/useServerSearch";
import { IPaginatedData } from "@/utils/constants";

const props = defineProps<{
  payments: IPaginatedData<IPayment>;
  income: number;
  outgoing: number;
  total: number;
  serverSearchOptions: IServerSearchData;
}>();

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

const filters = ref({
  account: "",
});

const onAccountChange = (account: Record<string, any>) => {
  pageState.filters.account = account.id;
  executeSearchWithDelay();
};

const setDirection = (direction: string | null) => {
  pageState.filters.direction = direction;
  executeSearchWithDelay();
};
</script>

<template>
  <AppLayout :title="$t('Payments')">
    <template #header>
      <div class="flex justify-end items-center px-5 py-1">
        <section class="flex items-center space-x-2">
          <div class="text-right space-x-2">
            <AccountSelect
              endpoint="/api/accounts"
              v-model="filters.account"
              @update:model-value="onAccountChange"
            />
          </div>
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            @change="executeSearchWithDelay()"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          />
        </section>
      </div>
    </template>
    <div class="mx-auto py-10 sm:px-6 lg:px-8">
      <section class="flex space-x-4">
        <AtBackgroundIconCard
          class="w-full bg-white border h-28 text-body-1"
          :title="$t('Incoming')"
          :value="formatMoney(income ?? 0)"
          @click="setDirection('debit')"
        />
        <AtBackgroundIconCard
          class="w-full bg-white border h-28 text-body-1 cursor-pointer"
          :title="$t('Outgoing')"
          :value="formatMoney(outgoing ?? 0)"
          @click="setDirection('credit')"
        />
        <AtBackgroundIconCard
          class="w-full bg-white border h-28 text-body-1"
          :title="$t('Month Balance')"
          :value="formatMoney(total || 0)"
          @click="setDirection(null)"
        />
      </section>

      <div class="mt-5 rounded-md bg-white">
        <div class="w-full">
          <AtTable :cols="cols" :tableData="payments.data" :show-prepend="true">
            <!-- Table Data -->
            <template v-slot:date="{ scope: { row } }">
              <div>
                <div class="font-bold text-blue-400">{{ formatDate(row.date) }}</div>
              </div>
            </template>

            <template v-slot:concept="{ scope: { row } }">
              <div
                class="border-dashed border-b border-blue-400 text-blue-400 cursor-pointer capitalize text-md"
              >
                {{ row.concept }} {{ row.id }}
                <span class="font-bold text-gray-300">
                  {{ row.series }} #{{ row.number }}
                </span>
              </div>
            </template>

            <template v-slot:status="{ scope: { row } }">
              <div class="font-bold capitalize">
                {{ row.status }}
              </div>
            </template>

            <template v-slot:total="{ scope: { row } }">
              <div class="font-bold">
                {{ formatMoney(row.amount) }}
              </div>
            </template>

            <template v-slot:debt="{ scope: { row } }">
              <div
                class="font-bold"
                :class="[row.debt > 0 ? 'text-red-500' : 'text-green-500']"
              >
                {{ formatMoney(row.debt) }}
              </div>
            </template>

            <template v-slot:actions="{ scope: { row } }">
              <div class="space-x-2 flex">
                <AtButton
                  @click="$inertia.visit(`payments/${row.id}/edit`)"
                  class="rounded-full text-gray-400 hover:text-green-400 w-8 h-8"
                >
                  <i class="fa fa-edit"></i>
                </AtButton>
                <AtButton class="rounded-full text-gray-400 hover:text-red-400 w-8 h-8">
                  <i class="fa fa-trash"></i>
                </AtButton>
              </div>
            </template>
            <!-- / Table data-->
          </AtTable>
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
