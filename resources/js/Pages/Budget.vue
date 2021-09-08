<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between">
                <div>
                <h2 class="text-xl font-semibold leading-tight text-pink-500">
                    Budget settings
                </h2>

                </div>

                <div>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto bg-white rounded-md shadow-lg max-w-7xl">
                <div class="flex px-5 space-x-2 border-b">
                    <at-field label="Category" class="w-7/12" v-if="categories.length">
                        <n-select
                            filterable
                            clearable
                            v-model:value="form.account_id"
                            :default-expand-all="true"
                            :options="categoryOptions"
                        >
                         <template #action>
                            <AtButton @click="isModalOpen = true" class="text-white bg-pink-500"> Add category </AtButton>
                         </template>
                        </n-select>
                        <at-error-bag v-if="errors" :errors="errors" field="account_id" />
                    </at-field>

                    <at-field label="Amount" class="w-4/12">
                        <at-input type="number" v-model="form.amount" />
                        <at-error-bag v-if="errors" errors="errors" field="amount" />
                    </at-field>

                    <div>
                        <at-field label="Action">
                            <at-button class="block bg-pink-500" @click="submit()"> Save </at-button>
                        </at-field>
                    </div>
                </div>

                <div class="px-5 pt-2 pb-10 space-y-3">
                    <div class="flex text-lg font-bold text-gray-400">
                        <div class="w-full"> Category</div>
                        <div class="w-full text-right"> Amount </div>
                    </div>
                    <budget-item
                        v-for="budget in budgets.data"
                        :key="budget"
                        show-delete
                        @deleted="deleteBudget(budget)"
                        :item="{
                            name: budget.account.name,
                            amount: budget.amount
                        }"
                    />
                    <budget-item
                        class="font-bold"
                        :item="{
                            name: 'Totals',
                            amount: budgetTotal
                        }"
                    />
                </div>

            </div>

            <category-modal
                @close="isModalOpen=false"
                v-model:show="isModalOpen"
            />
        </div>
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton, AtField, AtInput, AtErrorBag } from "atmosphere-ui";
    import CategoryModal from '../Components/CategoryModal.vue';
    import { useForm } from '@inertiajs/inertia-vue3';
    import { NSelect, NModal, NCard } from "naive-ui"
    import { computed, provide, reactive, toRefs } from 'vue';
    import BudgetItem from '../Components/molecules/BudgetItem.vue';
    import { useMoney } from "@/utils/useMoney";
    import { useSelect } from "@/utils/useSelects";
    import { Inertia } from '@inertiajs/inertia';

    export default {
        components: {
            AppLayout,
            AtButton,
            AtField,
            AtInput,
            AtErrorBag,
            CategoryModal,
            NSelect,
            NModal,
            NCard,
            BudgetItem,
        },
        props: {
            budgets: {
                type: Array,
                required: true
            },
            categories: {
                type: Array,
                required: true
            }
        },
        setup(props) {
            const { sumMoney } = useMoney();
            const { categoryOptions }  = useSelect();

            const state = reactive({
                isModalOpen: false,
                form: useForm({
                    amount: 0,
                    account_id: null,
                }),
                budgetTotal: computed(() => {
                    return sumMoney(props.budgets.data.map(item => item.amount));
                }),
                categoryOptions: computed(() => {
                    return categoryOptions(props.categories, true);
                })
            })

            provide('categoryOptions', state.categoryOptions);

            const deleteBudget = (budget) => {
                Inertia.delete(route('budgets.destroy', budget), {
                    onSuccess: () => {
                        Inertia.reload([
                            'budgets'
                        ]);
                    },
                })
            }

            const submit = () => {
                state.form.post('/budgets', {
                    onSuccess: () => {
                        state.form.reset();
                    }
                })
            }

            return {
                ...toRefs(state),
                submit,
                deleteBudget
            }
        }
    }
</script>
