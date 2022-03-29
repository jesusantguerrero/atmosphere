<template>
<AppLayout>
    <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">
        <section-title type="secondary">Financial Status</section-title>
        <TransactionsTable
            table-label="Transactions"
            class="pt-3 mt-5 "
            table-class="overflow-auto bg-white border rounded-lg shadow-md mt-5"
            allow-select
            show-sum
            :transactions="transactions"
            :parser="transactionDBToTransaction"
        >
            <template #action>
                <div class="flex items-center w-full space-x-2">
                    <AtDatePager
                        class="h-12 w-full"
                        v-model="state.date"
                        v-model:dateSpan="state.dateSpan"
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
    </div>
</AppLayout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout'
    import TransactionsTable from "@/Components/organisms/TransactionsTable";
    import { transactionDBToTransaction } from '@/utils/transactions';
    import { NSelect } from 'naive-ui'
    import { reactive, watch } from "vue";
    import { AtDatePager } from "atmosphere-ui"
    import { Inertia } from "@inertiajs/inertia";

    const props = defineProps({
        transactions: {
            type: Array,
            default: () => []
        },
        serverSearchOptions: {
            type: Object,
            default: () => ({})
        },
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
            group: ""
        },
        date: new Date(),
        dateSpan: null,
        listType: 'table'
    })

    Object.entries(props.serverSearchOptions).forEach(([key, value]) => {
        state.searchOptions[key] = value;
    })

    const updateSearch = (group) => {
        let params = ''
        params = Object.entries(state.searchOptions).map(([key, value]) => {
            return value ? `${key}=${value}` : ''
        }).filter(value => value).join('&')
        Inertia.replace(`/financial?${params}`)
    }

    watch(() => state.searchOptions, (group) => {
        updateSearch();
    }, { deep: true })

    const isSelectedList = (listTypeName) => {
        return state.listType == listTypeName
    }
</script>
