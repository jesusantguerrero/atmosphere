<template>
    <AppLayout>
        <div class="px-5 mx-auto mt-5 space-y-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8">
            <div class="md:w-9/12">
                <SectionTitle type="secondary"> Dashboard</SectionTitle>
                <BudgetTracker
                    class="mt-5"
                    ref="budgetTrackerRef"
                    :budget="budgetTotal"
                    :expenses="transactionTotal"
                    :username="user.name"
                    @section-click="selected=$event"
                />

                <div class="mt-5 mb-10" v-if="drafts.length">
                    <SectionTitle type="secondary">
                        Pending for approval
                    </SectionTitle>
                    <div class="px-5 py-1 mt-5 border rounded-md shadow-md border-slate-700 bg-slate-600">
                        <TransactionsTable
                            table-label="Automatic Transactions"
                            class="pt-3"
                            table-class="px-0 mt-5"
                            @approved="approveTransaction"
                            @removed="removeTransaction"
                            @edit="handleEdit"
                            :allow-mark-as-approved="true"
                            :allow-remove="true"
                            :transactions="drafts"
                            :parser="transactionDBToTransaction"
                        >
                            <template v-slot:action>
                                <div class="flex justify-end">
                                    <AtButton class="flex items-center h-10 space-x-2 text-pink-400" rounded @click="approveTransactionAll($event)">
                                        <i class="block mr-2 fa fa-check"></i> Approve
                                    </AtButton>
                                    <AtButton class="flex items-center h-10 mr-2 space-x-2 text-pink-400" rounded @click="removeAllDrafts()">
                                        <i class="block mr-2 fa fa-times"></i> Remove</AtButton>
                                    <AtButton class="flex items-center h-10 space-x-2 text-white bg-pink-400" rounded @click="runAutomations()">
                                        <i class="block fa fa-robot"></i>
                                    </AtButton>
                                </div>
                            </template>
                        </TransactionsTable>
                    </div>
                </div>
            </div>
            <div class="md:w-3/12">
                <div class="space-y-5">
                    <SectionTitle type="secondary"> Meals</SectionTitle>
                    <RandomMealCard />

                    <h4 class="text-2xl font-bold text-pink-400"> Menu for today</h4>
                    <div class="px-4 py-3 rounded-lg shadow-md cursor-pointer bg-slate-600" v-for="plan in meals" :key="plan.id">
                        <h4 class="font-bold text-blue-400">
                            {{ plan.dateable.name }}
                        </h4>
                        <small class="text-gray-400">Lunch</small>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
    import { AtButton } from "atmosphere-ui";
    import { ref } from 'vue';
    import { Inertia } from "@inertiajs/inertia";
    import AppLayout from '@/Layouts/AppLayout.vue'
    import BudgetTracker from "@/Components/organisms/BudgetTracker.vue";
    import TransactionsTable from "@/Components/organisms/TransactionsTable.vue";
    import SectionTitle from "@/Components/atoms/SectionTitle.vue";
    import RandomMealCard from "@/Components/RandomMealCard.vue";
    import { useSelect } from '@/utils/useSelects';
    import { transactionDBToTransaction } from "@/domains/transactions";

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

    const runAutomations = () => {
        axios.post('/api/automation/run-all')
            .then(response => {
                console.log(response.data)
            })
            .catch(error => {
                console.log(error)
            })
    }

    const removeAllDrafts = () => {
        Inertia.post('/transactions/remove-all-drafts')
            .then(response => {
                Inertia.reload()
            })
    }

    const approveTransactionAll = (transaction) => {
        Inertia.post(`/transactions/approve-all-drafts`).then(() => {
            Inertia.reload();
        })
    }
    const approveTransaction = (transaction) => {
        Inertia.post(`/transactions/${transaction.id}/approve`).then(() => {
            Inertia.reload();
        })
    }

    const removeTransaction = (transaction) => {
        Inertia.delete(`/transactions/${transaction.id}`).then(() => {
            Inertia.reload();
        })
    }

    const budgetTrackerRef = ref();
    const handleEdit = (transaction) => {
        budgetTrackerRef.setTransaction(transaction)
    }

    const { categoryOptions: transformCategoryOptions } = useSelect()
    transformCategoryOptions(props.categories, 'accounts', 'categoryOptions');
    transformCategoryOptions(props.accounts, 'accounts', 'accountsOptions');
</script>
