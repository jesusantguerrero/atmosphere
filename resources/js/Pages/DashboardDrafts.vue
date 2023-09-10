<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { ITransaction } from '@/domains/transactions/models';
    import axios from 'axios';
    import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
    import TransactionsList from '@/domains/transactions/components/TransactionsList.vue';
    import { removeTransaction, transactionDBToTransaction, useTransactionModal } from '@/domains/transactions';


    const transactionsDraft = ref([]);
    const isLoadingDrafts = ref(false);
    const fetchTransactions = () => {
        const url = `/api/finance/transactions?filter[status]=draft&limit=10`;
        return axios.get(url).then<ITransaction[]>(({ data }) => {
            transactionsDraft.value = data;
            isLoadingDrafts.value = false
        })
    }

    onMounted(() => {
        fetchTransactions()
    })

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
    <WidgetTitleCard title="Draft Transactions" class="hidden md:block">
        <TransactionsList
            class="w-full"
            table-class="w-full p-2 overflow-auto text-sm rounded-t-lg shadow-md bg-base-lvl-3"
            :transactions="transactionsDraft"
            :parser="transactionDBToTransaction"
            :allow-remove="true"
            :allow-mark-as-approved="true"
            :hide-accounts="true"
            @approved="handleEdit"
            @removed="removeTransaction($event, ['draft'])"
        />

        <template #action>
            <AtButton
                class="flex items-center text-primary"
                @click="router.visit('/transactions?filter[status]=planned')"
            >
                <span> See scheduled</span>
                <i class="ml-2 fa fa-chevron-right"></i>
            </AtButton>
        </template>
    </WidgetTitleCard>
</template>

