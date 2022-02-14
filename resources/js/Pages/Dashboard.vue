<template>
    <app-layout>
        <div class="px-5 mx-auto mt-5 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="md:w-9/12">
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
                        />
                        <div className="py-3 mt-5 text-center">
                            <AtButton class="text-pink-500" @click="selected=''"><i class="block fa fa-chevron-up"></i> Show less</AtButton>
                        </div>
                    </div>
                </budget-tracker>

                <div class="mt-5 mb-10">
                    <section-title type="secondary">
                        Pending for approval
                    </section-title>
                    <div class="px-5 mt-5 rounded-md shadow-md py-1 bg-white border">
                        <transactions-table
                            table-label="Automatic Transactions"
                            class="pt-3"
                            table-class="px-0 mt-5"
                            @approved="approveTransaction"
                            :allow-mark-as-approved="true"
                            :transactions="drafts"
                            :parser="transactionDBToTransaction"
                        />
                    </div>
                </div>
            </div>
            <div class="md:w-3/12">
                <div class="space-y-5">
                    <section-title type="secondary"> Meals</section-title>
                    <RandomMealCard />

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

<script setup>
    import { AtButton } from "atmosphere-ui";
    import AppLayout from '@/Layouts/AppLayout'
    import BudgetTracker from "@/Components/organisms/BudgetTracker";
    import TransactionsTable from "@/Components/organisms/TransactionsTable";
    import SectionTitle from "@/Components/atoms/SectionTitle";
    import { ref } from '@vue/reactivity';
    import { useSelect } from '@/utils/useSelects';
    import RandomMealCard from "../Components/RandomMealCard.vue";
    import { Inertia } from "@inertiajs/inertia";

    const props = defineProps({
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
            drafts: {
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
            id: transaction.id,
            title: transaction.description,
            date: transaction.date,
            subtitle: `${transaction.account.name} -> ${transaction.category.name} `,
            value: transaction.total,
            status: 'PENDING'
        }))
    }

    const approveTransaction = (transaction) => {
        Inertia.post(`/transactions/${transaction.id}/approve`).then(() => {
            Inertia.reload();
        })
    }

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, true, 'categoryOptions');
    transformCategoryOptions(props.accounts, true, 'accountsOptions');
</script>
