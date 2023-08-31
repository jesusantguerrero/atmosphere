<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted, watch , nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { format } from "date-fns";
import { NDatePicker } from "naive-ui";
import { debounce,  } from "lodash";
// @ts-expect-error: no definitions
import { AtField, AtDatePager } from "atmosphere-ui";
// import { AtDatePager, useServerSearch, IServerSearchData, AtField } from "atmosphere-ui";
import { useServerSearch, IServerSearchData } from "@/composables/useServerSearchV2";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import BackgroundCard from "@/Components/molecules/BackgroundCard.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTemplate from "@/domains/transactions/components/TransactionTemplate.vue";
import DraftButtons from "@/domains/transactions/components/DraftButtons.vue";

import { useTransactionModal } from "@/domains/transactions";
// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";
import { tableAccountCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import AccountReconciliationAlert from "@/domains/transactions/components/AccountReconciliationAlert.vue";
import AccountReconciliationBanner from "./Partials/AccountReconciliationBanner.vue";

const { openTransactionModal } = useTransactionModal();


interface CollectionData<T> {
		data: T[]
}
const props = withDefaults(defineProps<{
		transactions: ITransaction[];
		stats: CollectionData<Record<string, number>>;
		accounts: IAccount[];
		categories: ICategory[],
		serverSearchOptions: Partial<IServerSearchData>,
		accountId?: number,
}>(), {
		serverSearchOptions: () => {
				return {}
		}
});

const isLoading = ref(false);
const { serverSearchOptions, accountId, accounts } = toRefs(props);
const { state: pageState, hasFilters, reset } =
useServerSearch(serverSearchOptions);

provide("selectedAccountId", accountId);

const selectedAccount = computed(() => {
	return accounts.value.find((account) => account.id === accountId?.value);
});

const context = useAppContextStore();
const listComponent = computed(() => {
	return context.isMobile ? TransactionSearch : TransactionTemplate;
});

const isDraft = computed(() => {
	return serverSearchOptions.value.filters?.status == "draft";
});

const removeTransaction = (transaction: ITransaction) => {
	router.delete(`/transactions/${transaction.id}`, {
		onSuccess() {
			router.reload();
		},
	});
};

const findLinked = (transaction: ITransaction) => {
	router.patch(`/transactions/${transaction.id}/linked`, {
		// @ts-ignore
		onSuccess() {
			router.reload();
		},
	});
};

const handleEdit = (transaction: ITransaction) => {
	openTransactionModal({
		transactionData: transaction,
	});
};



onMounted(() => {
		router.on('start', () => isLoading.value = true)
		router.on('finish', () => isLoading.value = false)
})

const monthName = computed(() => format(pageState.dates.startDate, "MMMM"))

// reconciliation

const hasReconciliation = computed(() => {
    return selectedAccount.value?.reconciliations_pending
})

const reconcileForm = useForm({
		isVisible: false,
		date: new Date(),
		balance: 0,
})

const reconciliation = () => {
	reconcileForm.transform(data => ({
		...data,
		date: format(data.date, 'yyyy-MM-dd'),
	})).post(`/finance/reconciliation/accounts/${selectedAccount.value?.id}`, {
		onFinish() {
				reconcileForm.reset()
				reconcileForm.isVisible = false;
		}
	});
};

</script>

<template>
<AppLayout @back="router.visit('/finance/transactions')" :show-back-button="true">
  <template #header>
    <FinanceSectionNav>
      <template #actions>
        <div class="flex items-center w-full space-x-2">
          <AtDatePager
            class="w-full h-12 border-none bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
          />
          <LogerButton
          variant="inverse"
          @click="reconcileForm.isVisible = true"
          v-if="!hasReconciliation">
            Reconciliation
          </LogerButton>
          <LogerButton
            variant="inverse"
            @click="router.visit(`/finance/reconciliation/${selectedAccount?.reconciliations_pending.id}`)"
            v-else
          >
            Review Reconciliation
          </LogerButton>
          <DraftButtons v-if="isDraft" />
        </div>
      </template>
    </FinanceSectionNav>
  </template>
  <FinanceTemplate title="Transactions" :accounts="accounts">
      <section class="flex w-full mt-4 space-x-4 flex-nowrap">
        <BackgroundCard
          class="w-full cursor-pointer text-body-1 bg-base-lvl-3"
          v-for="(stat, label) in stats"
          :value="formatMoney(stat)"
          :label="label"
          label-class="capitalize text-secondary font-base"
        />
      </section>

      <section class="mt-4 bg-base-lvl-3">
        <header class="flex items-center justify-between px-6 py-2">
            <section>
                <h4 class="text-lg font-bold text-body-1">
                    All transactions in <span class="text-secondary">
                        {{ monthName }}
                    </span>
                </h4>
                <AppSearch
                    v-model.lazy="pageState.search"
                    class="w-full md:flex"
                    :has-filters="hasFilters"
                    @clear="reset()"
                />
            </section>
        <span>
            {{  transactions.length }} Results
        </span>
        </header>
        <AccountReconciliationBanner
            v-if="selectedAccount"
            :account="selectedAccount"
        />
          <Component
            :is="listComponent"
            :cols="tableAccountCols(props.accountId)"
            :transactions="transactions"
            :server-search-options="serverSearchOptions"
            :is-loading="isLoading"
            @findLinked="findLinked"
            @removed="removeTransaction"
            @edit="handleEdit"
          />
      </section>

      <ConfirmationModal
          :show="reconcileForm.isVisible"
          @close="reconcileForm.isVisible = false"
          title="Ending statement balance"
        >

          <template #content>
              <section>
                  <h4 class="font-bold">
                  {{ selectedAccount.name }}
                  </h4>
                  <AtField
                  label="Ending balance Date"
                  class="flex justify-between w-full md:w-4/12 md:block"
              >
                  <NDatePicker
                  v-model:value="reconcileForm.date"
                  type="date"
                  size="large"
                  class="w-48 md:w-full"
                  />
              </AtField>

              <AtField label="statement balance">
                  <LogerInput
                      ref="input"
                      class="opacity-100 cursor-text"
                      v-model="reconcileForm.balance"
                      :number-format="true"

                  >
                      <template #prefix>
                          {{ selectedAccount.currency_code }}
                      </template>
                  </LogerInput>
              </AtField>
              </section>

          </template>

          <template #footer>
              <section class="flex justify-between">
                  <LogerButton @click="reconcileForm.isVisible = false" variant="neutral">
                      Cancel
                  </LogerButton>

                  <LogerButton
                      class="ml-2"
                      @click="reconciliation"
                      :class="{ 'opacity-25': reconcileForm.processing }"
                      :disabled="reconcileForm.processing"
                  >
                      Save
                  </LogerButton>
              </section>
          </template>
      </ConfirmationModal>
  </FinanceTemplate>
</AppLayout>
</template>
