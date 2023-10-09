<script setup lang="ts">
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

import AppSearch from "@/Components/AppSearch/AppSearch.vue";

import TransactionTable from "@/domains/transactions/components/TransactionTable.vue";
import TransactionSearch from "@/domains/transactions/components/TransactionSearch.vue";

import { useTransactionModal } from "@/domains/transactions";
import { useAppContextStore } from "@/store";
import {  ITransaction } from "@/domains/transactions/models";


defineProps<{
    searchText: string;
    description: string;
    transactions: ITransaction[];
    isLoading: boolean;
}>();

// mobile
const context = useAppContextStore();
const showAllTransactions = ref(false);
const showTransactionTable = computed(() => {
  return context.isMobile ? showAllTransactions.value : true;
});
const listComponent = computed(() => {
  return context.isMobile ? TransactionSearch : TransactionTable;
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

const { openTransactionModal } = useTransactionModal();
const handleEdit = (transaction: ITransaction) => {
    axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
        openTransactionModal({
          transactionData: data,
        });
    })
};
</script>


<template>
    <main class="mt-4 bg-base-lvl-3">
    <header class="flex justify-between px-6 py-2">
        <section>
            <h4 class="text-lg font-bold text-body-1">
               {{ description }}
            </h4>
        </section>

        <section class="flex items-center space-x-2">
            <AppSearch
                :value="searchText"
                @update:modelValue="$emit('update:searchText', $event)"
                class="w-full md:flex"
                :has-filters="true"
                @clear="$emit('clear')"
                @blur="$emit('blur')"
            />
        <span>
            {{  transactions.length }}
        </span>
        </section>
    </header>
    <component
        v-if="showTransactionTable"
        :is="listComponent"
        :transactions="transactions"
        :is-loading="isLoading"
        all-accounts
        @findLinked="findLinked"
        @removed="removeTransaction"
        @edit="handleEdit"
    />
    </main>
</template>

