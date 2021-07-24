<template>
    <app-layout>
        <div class="flex mx-auto mt-5 space-x-10 max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="w-9/12">
                <section-title type="secondary"> Dashboard</section-title>
            </div>
            <div class="w-3/12">

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
