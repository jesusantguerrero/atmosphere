<template>
    <app-layout>
        <div class="px-6 pb-20 mx-auto mt-5 max-w-screen-2xl lg:px-8">
            <section-title type="secondary">
                Finance
                <button @click="hasHiddenValues=!hasHiddenValues">hidden</button>
            </section-title>
              <div class="flex flex-wrap md:flex-nowrap md:space-x-10">
                <div class="w-full md:w-6/12">
                    <div class="mt-5">
                        <SectionTitle type="secondary">Summary</SectionTitle>
                    </div>
                    <div class="flex flex-wrap justify-between px-5 py-5 mt-5 overflow-hidden border shadow-sm border-slate-700 bg-slate-600 rounded-xl">
                        <div class="mx-auto mb-4 space-y-4">
                            <FinanceCard
                                class="text-gray-100 bg-slate-500"
                                title="Income"
                                :hidden="hasHiddenValues"
                                :value="formatMoney(income)"
                                :subtitle="`Last Month: ${incomeVariance}%`"
                            />
                            <FinanceCard
                                class="text-gray-100 bg-slate-500"
                                title="Savings"
                                value="10,000,00"
                                :hidden="hasHiddenValues"
                                subtitle="Total: 150,000.00"
                            />
                            <AtButton @click="$inertia.visit('/goals')" class="w-full text-white bg-pink-500 rounded-md">
                                Goals
                            </AtButton>
                        </div>
                        <div class="px-5 py-5 mx-auto text-white bg-pink-500 cursor-pointer rounded-xl" @click="$inertia.visit('/financial')">
                            <h4> Expenses </h4>
                            <div class="mt-2 text-4xl font-bold"> {{ formatMoney(transactionTotal) }} </div>
                            <div class="px-5 py-2 mt-4 bg-gray-700 bg-opacity-25 rounded-3xl"> Last month variance:
                            <span class="font-bold">{{ expenseVariance }}%</span></div>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-6/12">
                    <TransactionsTable
                        table-label="Subscriptions"
                        class="pt-3 mt-5 "
                        table-class="overflow-auto border rounded-lg shadow-md bg-slate-600 "
                        :transactions="subscriptions"
                        :allow-mark-as-paid="true"
                        :parser="plannedDBToTransaction"
                        @edit="handleEdit"
                    >
                       <template #action>
                            <at-button class="text-pink-500" @click="openModalFor('subscription')"><i class="fa fa-plus"></i> Add subscription</at-button>
                        </template>
                    </TransactionsTable>
                </div>
            </div>
            <div class="flex flex-wrap mt-5 md:flex-nowrap md:space-x-10">
              <div class="w-full md:w-6/12">
                   <TransactionsTable
                        table-label="My payments"
                        class="pt-3 mt-5"
                        table-class="border rounded-lg shadow-md bg-slate-600"
                        @paid-clicked="markAsPaid"
                        :allow-mark-as-paid="true"
                        :transactions="planned"
                        :parser="plannedDBToTransaction"
                    >
                    <template #action>
                        <at-button class="text-pink-500" @click="openModalFor('transaction')"><i class="fa fa-plus"></i> Add planned payment</at-button>
                    </template>
                   </TransactionsTable>
                </div>
                <div class="w-full md:w-6/12">
                    <TransactionsTable
                        table-label="Transactions"
                        class="pt-3 mt-5 "
                        table-class="border rounded-lg shadow-md bg-slate-600"
                        :transactions="transactions"
                        :parser="transactionDBToTransaction"
                        @edit="handleEdit"
                    >
                      <template #action>
                        <at-button class="text-pink-500" @click="openModalFor()"><i class="fa fa-plus"></i> Add transaction</at-button>
                        </template>
                    </TransactionsTable>
                </div>
            </div>

            <transaction-modal
                @close="isTransferModalOpen=false"
                v-bind="transferConfig"
                v-model:show="isTransferModalOpen"
            />
        </div>
    </app-layout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout'
    import FinanceCard from "@/Components/molecules/FinanceCard";
    import TransactionsTable from "@/Components/organisms/TransactionsTable";
    import SectionTitle from "@/Components/atoms/SectionTitle";
    import { reactive, ref, provide, computed } from 'vue';
    import { useSelect } from '@/utils/useSelects';
    import formatMoney from '@/utils/formatMoney';
    import { transactionDBToTransaction } from '@/utils/transactions';
    import { Inertia } from '@inertiajs/inertia';
    import TransactionModal from "@/Components/TransactionModal.vue"

    const props = defineProps({
            user: {
                type: Object,
                required: true,
            },
            planned: {
                type: Array,
                default()  {
                    return [];
                }
            },
            subscriptions: {
                type: Array,
                default()  {
                    return [];
                }
            },
            budgetTotal: {
                type: Number,
                default: 0,
            },
            budget: {
                type: Array,
                default() {
                    return []
                }
            },
            income: {
                type: Number,
                default: 0,
            },
            lastMonthIncome: {
                type: Number,
                default: 0,
            },
            transactionTotal: {
                type: Number,
                default: 0,
            },
            lastMonthExpenses: {
                type: Number,
                default: 0,
            },
            transactions: {
                type: Array,
                default() {
                    return []
                }
            },
            categories: {
                type: Array,
                default() {
                    return []
                }
            },
            accounts: {
                type: Array,
                default() {
                    return []
                }
            }
    });

    const hasHiddenValues = ref(false)
    provide('hasHiddenValues', hasHiddenValues)

    const selected = ref(null);

    const plannedDBToTransaction = (transactions) => {
        return transactions.map(transaction => ({
            id: transaction.id,
            date: transaction.date,
            title: transaction.description,
            subtitle: `${transaction.account.name} -> ${transaction.category.name} `,
            value: transaction.total,
            status: 'PLANNED'
        }))
    }

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, 'accounts', 'categoryOptions');
    transformCategoryOptions(props.accounts, 'accounts', 'accountsOptions');
    const getVariances = (current, last) => {
        if (last === 0) {
            return 0;
        }
        const variance = (current - last) / (last * 100)
        return variance.toFixed(2);
    }

    const incomeVariance = computed(() => {
        return getVariances(props.income, props.lastMonthIncome);
    });

    const expenseVariance = computed(() => {
        return getVariances(props.transactionTotal, props.lastMonthExpenses) || 0;
    });

    const markAsPaid = (transaction) => {
        Inertia.put(`/transactions/${transaction.id}/mark-as-paid`, {}, {
            onSuccess: () => {
                Inertia.reload();
            }
        })
    }

    const isTransferModalOpen = ref(false);
    const transferConfig = reactive({
        recurrence: false,
        automatic: false,
        transactionData: null
    })

    const openModalFor = (isRecurrent, automatic) => {
        isTransferModalOpen.value = true;
        transferConfig.recurrence = isRecurrent;
        transferConfig.automatic = automatic;
    }

    const handleEdit = (transaction) => {
        transferConfig.transactionData = transaction;
        isTransferModalOpen.value = true;
    }
</script>
