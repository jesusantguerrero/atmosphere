<template>
    <app-layout>
        <div class="flex mx-auto mt-5 space-x-10 max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="w-9/12">
                <section-title type="secondary"> Dashboard</section-title>
                <budget-tracker
                    class="mt-5"
                    :budget="budgetTotal"
                    :expenses="transactionTotal"
                    :username="user.name"
                    @section-click="selected=$event"
                >
                    <div class="pt-5 mt-2" v-if="selected">
                        <transactions-table
                            v-if="selected=='expenses'"
                            table-label="Expenses"
                            class="pt-3 mt-5"
                            table-class="px-0 mt-5"
                            :transactions="transactions"
                            :parser="transactionDBToTransaction"
                        />
                        <transactions-table
                            v-if="selected=='budget'"
                            table-label="Budget"
                            username="Jesus"
                            class="pt-3 mt-5"
                            table-class="px-0 mt-5"
                            :transactions="budget"
                            :parser="budgetToTransaction"
                        />
                        <div className="py-3 mt-5 text-center">
                            <AtButton class="text-pink-500" @click="selected=''"><i class="block fa fa-chevron-up"></i> Show less</AtButton>
                        </div>
                    </div>
                </budget-tracker>
            </div>
            <div class="w-3/12">
                <div class="space-y-5">
                    <section-title type="secondary"> Meals</section-title>
                    <div class="px-4 py-3 bg-white rounded-lg shadow-md">
                        <div class="flex justify-between">
                            <h4 class="text-2xl font-bold text-blue-700"> Get random meal</h4>
                            <at-button class="text-white bg-pink-500 rounded-full"> <i class="fa fa-sync"></i></at-button>
                        </div>
                        <div class="h-20">
                        </div>
                    </div>

                    <h4 class="text-2xl font-bold text-pink-500"> Menu for today</h4>
                    <div class="px-4 py-3 bg-white rounded-lg shadow-md cursor-pointer" v-for="plan in meals" :key="plan.id">
                        <h4 class="font-bold text-blue-700">
                            {{ plan.dateable.name }}
                        </h4>
                        <small class="text-gray-400">Lunch</small>
                    </div>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
    import { AtButton } from "atmosphere-ui/dist/atmosphere-ui.es.js";
    import AppLayout from '@/Layouts/AppLayout'
    import Welcome from '@/Components/Welcome'
    import BudgetTracker from "@/Components/organisms/BudgetTracker";
    import TransactionsTable from "@/Components/organisms/TransactionsTable";
    import SectionTitle from "@/Components/atoms/SectionTitle";
    import { ref } from '@vue/reactivity';
    import { useSelect } from '@/utils/useSelects';

    export default {
        components: {
            AppLayout,
            Welcome,
            AtButton,
            BudgetTracker,
            SectionTitle,
            TransactionsTable
        },
        props: {
            meals: {
                type: Array,
                required: true,
            },
            user: {
                type: Object,
                required: true,
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
            transactionTotal: {
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
        },
        setup(props) {
            const selected = ref(null);

            const budgetToTransaction = (budgets) => {
                return budgets.map(budget => ({
                    title: budget.account.name,
                    subtitle: '',
                    value: budget.amount,
                    status: 'PENDING'
                }))
            }

            const transactionDBToTransaction = (transactions) => {
                return transactions.map(transaction => ({
                    title: transaction.description,
                    subtitle: `${transaction.account.name} -> ${transaction.category.name} `,
                    value: transaction.total,
                    status: 'PENDING'
                }))
            }

            const { categoryOptions: transformCategoryOptions } = useSelect()
            transformCategoryOptions(props.categories[0].subcategories, true, 'categoryOptions');
            transformCategoryOptions(props.accounts, true, 'accountsOptions');

            return {
                selected,
                budgetToTransaction,
                transactionDBToTransaction,
            }
        }
    }
</script>
