<template>
    <div class="pb-20 mt-5">
        <div class="flex items-center ml-auto w-72 space-x-2">
            <AtDatePager
                class="h-12 w-full bg-slate-600 text-gray-200 border-none"
                v-model="state.date"
                v-model:dateSpan="state.dateSpan"
                v-model:startDate="state.searchOptions.date.startDate"
                v-model:endDate="state.searchOptions.date.endDate"
                controlsClass="bg-transparent text-gray-200 hover:bg-slate-600"
                next-mode="month"
            />
        </div>
        <CustomTable
            :cols="tableCols"
            :show-prepend="true"
            :table-data="transactions"
            @edit="handleEdit"
        >
            <template v-slot:total="{ scope: { row } }">
                <div class="font-bold" :class="{'text-red-400':  row.direction == 'WITHDRAW', 'text-green-500': row.direction == 'DEPOSIT'}">
                    {{ formatMoney(row.total) }}
                </div>
            </template>
        </CustomTable>


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
    import CustomTable from "@/Components/atoms/CustomTable.vue";
    import { updateSearch, getDateFromIso } from '@/utils';
    import TransactionModal from "../TransactionModal.vue";
    import { tableCols } from '@/domains/transactions';
    import formatMoney from '@/utils/formatMoney';

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
</script>
