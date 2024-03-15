<script setup lang="ts">
import { computed, toRefs, provide, ref, onMounted } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { format } from "date-fns";
import { AtDatePager } from "atmosphere-ui";
import { useServerSearch, IServerSearchData } from "@/composables/useServerSearchV2";

import AppLayout from "@/Components/templates/AppLayout.vue";
import AppSearch from "@/Components/AppSearch/AppSearch.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import BackgroundCard from "@/Components/molecules/BackgroundCard.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import AccountReconciliationBanner from "./Partials/AccountReconciliationBanner.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";
import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";
import DraftButtons from "@/domains/transactions/components/DraftButtons.vue";

import { useTransactionModal, TRANSACTION_DIRECTIONS, removeTransaction } from "@/domains/transactions";
// import { IServerSearchData, useServerSearch } from "@/composables/useServerSearch";
import { tableAccountCols } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import { formatMoney } from "@/utils";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import axios from "axios";
import AccountReconciliationForm from "./AccountReconciliationForm.vue";


const { openTransactionModal } = useTransactionModal();


interface CollectionData<T> {
    data: T[]
}
const props = withDefaults(defineProps<{
    accountDetailTypes: {label: string, id: number| string}[];
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
	return context.isMobile ? TransactionSearch : TransactionTable;
});

const isDraft = computed(() => {
	return serverSearchOptions.value?.filters?.status == "draft";
});

const handleDuplicate = (transaction: ITransaction) => {
    axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
        delete data.id;
        openTransactionModal({
          transactionData: data,
        });
    })
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
    return selectedAccount.value?.reconciliations_last
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
            preserveScroll: true,
            only: ['transactions', 'accounts', 'stats'],
            onFinish() {
                reconcileForm.reset()
                reconcileForm.isVisible = false;

            }
        });
    };

    const  { TRANSFER } = TRANSACTION_DIRECTIONS;
    const page = usePage().props;

    const creditCard = computed(() => {
        return props.accountDetailTypes.find((type) => type.label.toLowerCase() == "credit cards");
    });
    const isCreditCard = computed(() => {
        return selectedAccount.value?.account_detail_type_id == creditCard.value?.id;
    });

    const payCreditCard = () => {
        const accountId = page.accountId
        const debt = Math.abs(selectedAccount.value?.balance ?? 0);
        openTransactionModal({
            mode: TRANSFER,
            transactionData: {
                counter_account_id: accountId ?? "",
                total: debt,
                description: `Payment of ${selectedAccount.value?.name}`,
                account_id: props.accounts.find((account) => account.balance > debt)?.id
            },
        })
    }

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
            @click="router.visit(`/finance/reconciliation/${selectedAccount?.reconciliations_last.id}`)"
            v-else
          >
            Review Reconciliation
          </LogerButton>
          <LogerButton
            variant="neutral"
            v-if="isCreditCard"
            @click="payCreditCard"
          >
            Pay credit card
          </LogerButton>
          <DraftButtons v-if="isDraft" />
        </div>
      </template>
    </FinanceSectionNav>
  </template>

  <template #title>
    <section class="flex items-center">
        <span>{{ selectedAccount.name }}</span>
        <button
            @click="router.visit(`/finance/accounts/${selectedAccount.id}/reconciliations/`)"
            title="reconciliations"
            class="inline-block ml-2 font-bold text-secondary"
        >
            <IMdiHistory />
        </button>
    </section>
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
            </section>
            <section class="flex items-center space-x-2">
                <AppSearch
                    v-model.lazy="pageState.search"
                    class="w-full md:flex "
                    :has-filters="hasFilters"
                    @clear="reset()"
                />

                <span class="min-w-fit text-secondary font-bold">
                    {{  transactions.length }} Results
                </span>
            </section>
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
                @removed="removeTransaction($event, ['verified'])"
                @duplicate="handleDuplicate"
                @edit="handleEdit"
            />
      </section>

      <AccountReconciliationForm
          :show="reconcileForm.isVisible"
          @close="reconcileForm.isVisible = false"
          :account="selectedAccount"
       />
  </FinanceTemplate>
</AppLayout>
</template>
