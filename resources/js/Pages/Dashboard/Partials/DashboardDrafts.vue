<script setup lang="ts">
    import { ref, onMounted } from 'vue';
    import axios from 'axios';

    import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';
    import TransactionsList from '@/domains/transactions/components/TransactionsList.vue';
    import LogerButton from '@/Components/atoms/LogerButton.vue';
    import { useTransactionStore } from '@/store/transactions';
    // @ts-expect-error: no types
    import MdiSync from '~icons/mdi/sync'

    import { ITransaction } from '@/domains/transactions/models';
    import { removeTransaction, draftsDBToTransaction, useTransactionModal } from '@/domains/transactions';


    const transactionsDraft = ref([]);
    const isLoadingDrafts = ref(false);
    const selected = defineModel('selected');
    const emit = defineEmits(['re-loaded']);
    
    const fetchTransactions = async () => {
        const url = `/api/finance/transactions?filter[status]=draft&limit=10&relationships=linked`;
        return axios.get(url).then<ITransaction[]>(({ data }) => {
            transactionsDraft.value = data;
            isLoadingDrafts.value = false
            return data;
        })
    }

    onMounted(() => {
        isLoadingDrafts.value = true
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
    transactionStore.$onAction(({
        name,
        store,
        args,
        after
    }) => {
        after((result) => {
            const [savedValue, action, originalData] = args;
            if ((originalData && originalData.status == 'draft' && savedValue.status == 'verified') || action == 'delete') {
                fetchTransactions();
                emit('re-loaded')
            }

            if (name == 'reload') {
                updateTransactions()
                emit('re-loaded');
            }
        })
    })

</script>

<template>
    <WidgetTitleCard class="md:block" :with-padding="false" :border="false">
        <TransactionsList
            v-if="!isLoadingDrafts"
            class="w-full"
            table-class="w-full p-2 overflow-auto text-sm rounded-t-lg shadow-md bg-base-lvl-3"
            v-model:selected="selected"
            :transactions="transactionsDraft"
            :parser="draftsDBToTransaction"
            :allow-remove="true"
            allow-match
            :allow-mark-as-approved="true"
            :hide-accounts="true"
            @approved="handleEdit"
            @removed="removeTransaction($event, ['draft'])"
        />

        <template #top>
            <section class="w-full">
                <LogerButton
                    v-if="isLoadingDrafts"
                    variant="inverse"
                    class="rounded-full w-full justify-center"
                    @click="updateTransactions()"
                    :processing="isLoading|| isLoadingDrafts"
                    :icon="MdiSync"
                />
            </section>
        </template>
    </WidgetTitleCard>
</template>

