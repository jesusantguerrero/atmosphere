<template>

    <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">
        <section-title type="secondary">{{ title }}</section-title>
        <TransactionsTable
            table-label="Transactions"
            class="pt-3 mt-5 "
            table-class="overflow-auto bg-slate-600 border rounded-lg shadow-md mt-5"
            allow-select
            show-sum
            :transactions="transactions"
            :parser="transactionDBToTransaction"
            @edit="handleEdit"
        >
            <template #action>
                <div class="flex items-center w-full space-x-2">
                    <AtDatePager
                        class="h-12 w-full bg-slate-600 text-gray-200 border-none"
                        v-model="state.date"
                        v-model:dateSpan="state.dateSpan"
                        v-model:startDate="state.searchOptions.date.startDate"
                        v-model:endDate="state.searchOptions.date.endDate"
                        controlsClass="bg-transparent text-gray-200 hover:bg-slate-600"
                        next-mode="month"
                    />
                    <n-select
                        filterable
                        clearable
                        size="large"
                        placeholder="Group by"
                        :options="state.filterOptions"
                        v-model:value="state.searchOptions.group"
                    />
                    <div class="flex text-white rounded-md bg-pink-400 h-10 min-w-max divide-x-2 divide-white overflow-hidden">
                        <button class="px-5" :class="{'bg-pink-700': isSelectedList('table')}" @click="state.listType = 'table'">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--ic" width="32" height="32" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"></path></svg></button>
                        <button class="px-5" :class="{'bg-pink-700' : isSelectedList('graph') }" @click="state.listType = 'graph'">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--uim" width="32" height="32" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M6 23H2a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1Z" opacity=".25"></path><path fill="currentColor" d="M14 23h-4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v20a1 1 0 0 1-1 1Z"></path><path fill="currentColor" d="M22 23h-4a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1Z" opacity=".5"></path></svg>
                        </button>
                    </div>
                </div>
            </template>
        </TransactionsTable>

        <TransactionModal
            @close="isTransferModalOpen=false"
            v-bind="transferConfig"
            v-model:show="isTransferModalOpen"
        />
    </div>
</template>

<script setup>
    import { NSelect } from 'naive-ui'
    import { reactive, ref, watch } from "vue";
    import { AtDatePager } from "atmosphere-ui"
    import { startOfDay } from 'date-fns';
    import { Inertia } from "@inertiajs/inertia";
    import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
    import { updateSearch, getDateFromIso } from '@/utils';
    import TransactionModal from "../TransactionModal.vue";
    import { transactionDBToTransaction } from '@/utils/transactions';

    const props = defineProps({
        transactions: {
            type: Array,
            default: () => []
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
        title: {
            type: String,
            default: 's'
        }
    })

    const state = reactive({
        filterOptions: [
            {
                label: 'Accounts',
                value: 'accounts'
            },
            {
                label: 'Descriptions',
                value: 'description'
            },
        ],
        searchOptions: {
            group: "",
            date: {
                startDate: null,
                endDate: null,
            }
        },
        date: startOfDay(props.serverSearchOptions?.date?.startDate || new Date()),
        dateSpan: null,
        listType: 'table'
    })

    watch(() => state.searchOptions, () => {
        updateSearch(state.searchOptions, state.dateSpan);
    }, { deep: true })

    Object.entries(props.serverSearchOptions).forEach(([key, value]) => {
        if (key === 'date') {
            state.searchOptions.date.startDate = startOfDay(getDateFromIso(value.startDate))
            state.searchOptions.date.endDate = startOfDay(getDateFromIso(value.endDate))
            state.date = startOfDay(getDateFromIso(value.startDate))
        } else {
            state.searchOptions[key] = Object.values(state.filterOptions).map(filter => filter.value).includes(value) ? value : ""
        }
    })

    const isSelectedList = (listTypeName) => {
        return state.listType == listTypeName
    }

    const isTransferModalOpen = ref(false);
    const transferConfig = reactive({
        recurrence: false,
        automatic: false,
        transactionData: null
    })

    const handleEdit = (transaction) => {
        transferConfig.transactionData = transaction;
        isTransferModalOpen.value = true;
    }

    const importTransaction = () => {
        const file = document.querySelector('input[type="file"]').files[0];
        Inertia.post('/financial/import', {
            file
        })
    }
</script>
