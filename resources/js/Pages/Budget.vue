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
            <div class="mx-auto max-w-7xl sm:px-6">
                <div class="flex space-x-2">
                    <at-field label="Amount">
                        <at-input type="number" v-model="form.amount" />
                    </at-field>

                    <at-field label="Category" v-if="categories.length">
                        <n-select
                            filterable
                            clearable
                            v-model:value="form.account_id"
                            :default-expand-all="true"
                            :options="categoryOptions"
                        />
                    </at-field>

                    <div>
                        <at-field label="Action">
                            <at-button class="block bg-pink-500" @click="submit()"> Save </at-button>
                        </at-field>
                    </div>
                </div>
                <budget-item
                    v-for="budget in budgets.data"
                    :key="budget"
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
    </app-layout>
</template>

<script>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton, AtField, AtInput } from "atmosphere-ui/dist/atmosphere-ui.es.js";
    import MealSection from '@/Components/Meal';
    import MealModal from '../Components/MealModal.vue';
    import { useForm } from '@inertiajs/inertia-vue3';
    import { NSelect } from "naive-ui"
    import { computed, reactive, toRefs } from 'vue';
    import BudgetItem from '../Components/molecules/BudgetItem.vue';
    import { useMoney } from "@/utils/useMoney";

    export default {
        components: {
            AppLayout,
            MealSection,
            AtButton,
            AtField,
            AtInput,
            MealModal,
            NSelect,
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
                    return props.categories[0].subcategories.map(category => {
                        category.type = 'group';
                        category.key = category.id;
                        category.label = category.name;
                        category.children = category.accounts.map(subCategory => ({
                            value: subCategory.id,
                            label: subCategory.name

                        }));
                        return category;
                    })
                })
            })

            const submit = () => {
                state.form.post('/budgets')
            }

            return {
                ...toRefs(state),
                submit
            }
        }
    }
</script>
