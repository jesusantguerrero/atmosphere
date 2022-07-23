<template>
    <AppLayout>
        <template #header>
            <FinanceSectionNav>
                <template #actions>
                    <div>
                        <AtButton class="text-white rounded-md bg-primary">Import Transactions</AtButton>
                    </div>
                </template>
            </FinanceSectionNav>
        </template>

        <FinanceTemplate title="Finance" :accounts="accounts" ref="financeTemplateRef">
            <section>
                <div class="flex flex-wrap md:flex-nowrap md:space-x-8">
                    <div class="w-full md:w-7/12">
                        <div class="mt-5">
                            <SectionTitle type="secondary">Summary</SectionTitle>
                        </div>
                        <div class="flex justify-between px-4 py-5 mt-3 space-x-4 overflow-hidden border shadow-sm flex-nowrap border-base bg-base-lvl-3 rounded-xl">
                            <div class="w-full mx-auto space-y-2">
                                <FinanceCard
                                    class="text-body-1 bg-base-lvl-1"
                                    title="Income"
                                    :hidden="hasHiddenValues"
                                    :value="formatMoney(income)"
                                    :subtitle="`Last Month: ${incomeVariance}%`"
                                />
                                <FinanceCard
                                    class="text-body-1 bg-base-lvl-1"
                                    title="Savings"
                                    :value="formatMoney('10000')"
                                    :hidden="hasHiddenValues"
                                    subtitle="Total: 150,000.00"
                                />
                                <AtButton @click="$inertia.visit('/goals')" class="w-full h-10 mt-auto text-white rounded-md bg-primary">
                                    Goals
                                </AtButton>
                            </div>
                            <FinanceVarianceCard
                                class="w-full"
                                @click="$inertia.visit('/finance/transactions')"
                                title="Expenses"
                                :value="formatMoney(transactionTotal)"
                                variance-title="Last month variance"
                                :variance="expenseVariance"
                            />
                        </div>
                    </div>
                    <div class="w-full md:w-5/12">
                        <TransactionsTable
                            table-label="Budget"
                            class="pt-3 mt-5 "
                            table-class="overflow-auto text-sm rounded-t-lg shadow-md bg-base-lvl-3"
                            :transactions="topCategories"
                            :parser="categoryDBToTransaction"
                            @edit="handleEdit"
                        >
                            <template #action>
                                <AtButton class="flex items-center text-primary" @click="Inertia.visit('/budgets')">
                                    <span>
                                        Go to Budget
                                    </span>
                                    <i class="ml-2 fa fa-chevron-right"></i>
                                </AtButton>
                            </template>
                        </TransactionsTable>
                        <BudgetProgress :goal="budgetTotal" :current="transactionTotal" class="border-t "/>
                    </div>
                </div>
                <div class="flex flex-wrap mt-5 md:flex-nowrap md:space-x-8">
                    <div class="w-full md:w-7/12">
                        <SectionCard
                            section-title="Expenses by category"
                            :action="{
                                label: 'Go to Trends',
                                iconClass: 'fa fa-chevron-right'
                            }"
                            @action="Inertia.visit('/finance/trends')">
                            <DonutChart  :series="expensesByCategoryGroup" label="name" value="total" />
                        </SectionCard>
                    </div>
                    <div class="w-full md:w-5/12">
                        <TransactionsTable
                            table-label="Transactions"
                            class="pt-3 mt-5 "
                            table-class="border rounded-lg shadow-md border-base bg-base-lvl-3"
                            :transactions="transactions"
                            :parser="transactionDBToTransaction"
                            @edit="handleEdit"
                        >
                        <template #action>
                            <at-button class="text-primary" @click="openModalFor()"><i class="fa fa-plus"></i> Add transaction</at-button>
                        </template>
                        </TransactionsTable>
                    </div>
                </div>
            </section>
        </FinanceTemplate>
    </AppLayout>
</template>

<script setup>
    import { computed, ref } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import { AtButton } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout.vue'
    import FinanceCard from "@/Components/molecules/FinanceCard.vue";
    import FinanceVarianceCard from "@/Components/molecules/FinanceVarianceCard.vue";
    import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
    import SectionTitle from "@/Components/atoms/SectionTitle.vue";
    import { useSelect } from '@/utils/useSelects';
    import formatMoney from '@/utils/formatMoney';
    import FinanceTemplate from '@/Components/templates/FinanceTemplate.vue';
    import { useTransactionModal, transactionDBToTransaction, categoryDBToTransaction, plannedDBToTransaction } from '@/domains/transactions'
    import BudgetProgress from '@/Components/molecules/BudgetProgress.vue';
    import DonutChart from '@/Components/organisms/DonutChart.vue';
    import SectionCard from '@/Components/molecules/SectionCard.vue';
    import FinanceSectionNav from '@/Components/templates/FinanceSectionNav.vue';

    const financeTemplateRef = ref(null)
    const { openModalFor, handleEdit } = useTransactionModal(financeTemplateRef)
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
            expensesByCategory: {
                type: Array,
                default()  {
                    return [];
                }
            },
            expensesByCategoryGroup: {
                type: Array,
                default()  {
                    return [];
                }
            },
            budgetTotal: {
                type: Number,
                default: 0,
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

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, 'accounts', 'categoryOptions');
    transformCategoryOptions(props.accounts, 'accounts', 'accountsOptions');
    const getVariances = (current, last) => {
        if (last === 0) {
            return 0;
        }
        const variance = (current - last) / last * 100
        return variance.toFixed(2);
    }

    const incomeVariance = computed(() => {
        return getVariances(props.income, props.lastMonthIncome);
    });

    const expenseVariance = computed(() => {
        return getVariances(props.transactionTotal, props.lastMonthExpenses) || 0;
    });

    const topCategories = props.expensesByCategory.slice(0, 4)
</script>
