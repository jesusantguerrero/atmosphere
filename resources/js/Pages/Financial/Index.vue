<template>
<AppLayout>
    <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">
        <section-title type="secondary">Financial Status</section-title>
        <TransactionsTable
            table-label="Transactions"
            class="pt-3 mt-5 "
            table-class="overflow-auto bg-white border rounded-lg shadow-md mt-5"
            allow-select
            :transactions="transactions"
            :parser="transactionDBToTransaction"
        >
            <template #action>
                <div class="flex items-center">
                    <n-select
                        filterable
                        clearable
                        size="large"
                        placeholder="Group by"
                        :options="state.filterOptions"
                        v-model:value="state.searchOptions.group"
                    />
                    <button>List</button>
                    <button>Graphic</button>
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
    import { makeOptions } from '@/utils/naiveui';
    import { reactive, watch } from "vue";
    import { Inertia } from "@inertiajs/inertia";

    defineProps({
        transactions: {
            type: Array,
            default: () => []
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
            group: []
        }
    })

    const updateSearch = (group) => {
        let params = ''
        params = Object.entries(state.searchOptions).map(([key, value]) => {
            return `${key}=${value}`
        }).join('&')
        Inertia.replace(`/financial?${params}`)
    }

    watch(() => state.searchOptions, (group) => {
        updateSearch();
    }, { deep: true })
</script>
