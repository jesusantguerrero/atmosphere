<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import { ITransaction } from '@/domains/transactions/models';
    import axios from 'axios';
    import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
    import TransactionsList from '@/domains/transactions/components/TransactionsList.vue';
    import { removeTransaction, transactionDBToTransaction, useTransactionModal } from '@/domains/transactions';
    import LogerButton from '@/Components/atoms/LogerButton.vue';
import { useTransactionStore } from '@/store/transactions';


    const transactionsDraft = ref([]);
    const isLoadingDrafts = ref(false);
    const fetchTransactions = async () => {
        const url = `/api/finance/transactions?filter[status]=draft&limit=10`;
        return axios.get(url).then<ITransaction[]>(({ data }) => {
            transactionsDraft.value = data;
            isLoadingDrafts.value = false
        })
    }

    onMounted(() => {
        fetchTransactions()
    })

    const isLoading = ref(false);
    const updateTransactions = () => {
        isLoading.value = true;
        fetchTransactions().finally(() => {
            isLoading.value = false;
        })
    }

    const { openTransactionModal } = useTransactionModal();
    const handleEdit = (transaction: ITransaction) => {
        axios.get(`/transactions/${transaction.id}?json=true`).then(({ data }) => {
            openTransactionModal({
                transactionData: data,
            });
        })
    };

    const transactionStore = useTransactionStore();
    const unsubscribe =  transactionStore.$onAction(({
        name,
        store,
        args,
        after
    }) => {
        after((result) => {
            const [savedValue, action, originalData] = args;
            if (originalData && originalData.status == 'draft' && savedValue.status == 'verified') {
                fetchTransactions();
            }
        })
    })
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
            <LogerButton variant="inverse" class="rounded-full" @click="updateTransactions()" :disabled="isLoading">
                <div :class="{'animate-spin': isLoading}">
                    <IMdiSync  />
                </div>
            </LogerButton>
        </template>
    </WidgetTitleCard>
</template>

