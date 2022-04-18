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
                    <at-button class="text-white bg-pink-400" @click="isAddingGroup=true">
                        Add Budget Group
                    </at-button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto bg-white rounded-md shadow-lg max-w-7xl">
                <div class="flex px-5 space-x-2 border-b">
                    <AtField label="Category" class="w-7/12" v-if="categories.length">
                        <NSelect
                            v-if="!isAddingGroup"
                            filterable
                            clearable
                            size="large"
                            v-model:value="form.account_id"
                            :default-expand-all="true"
                            :options="categoryOptions"
                        >
                         <template #action>
                            <AtButton @click="isModalOpen = true" class="text-white bg-pink-500"> Add category </AtButton>
                         </template>
                        </NSelect>
                        <AtInput v-model="form.name" v-else />
                        <AtErrorBag v-if="errors" :errors="errors" field="account_id" />
                    </AtField>

                    <AtField label="Amount" class="w-4/12">
                        <AtInput type="number" v-model="form.amount" />
                        <AtErrorBag v-if="errors" errors="errors" field="amount" />
                    </AtField>

                    <div>
                        <AtField label="Action">
                            <at-button class="block text-white bg-pink-500 h-full" @click="submit()"> Save </at-button>
                        </AtField>
                    </div>
                </div>

                <div class="px-5 pt-2 pb-10 space-y-3">
                    <div class="flex text-lg font-bold text-gray-400">
                        <div class="w-full"> Category</div>
                        <div class="w-full text-right"> Amount </div>
                    </div>
                    <BudgetItem
                        v-for="budget in budgets.data"
                        :key="budget"
                        show-delete
                        @deleted="deleteBudget(budget)"
                        :item="{
                            name: budget.name,
                            amount: budget.amount
                        }"
                    />
                    <BudgetItem
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

<script setup>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton, AtField, AtInput, AtErrorBag } from "atmosphere-ui";
    import CategoryModal from '../Components/CategoryModal.vue';
    import { useForm } from '@inertiajs/inertia-vue3';
    import { NSelect } from "naive-ui"
    import { computed, reactive, toRefs } from 'vue';
    import BudgetItem from '../Components/molecules/BudgetItem.vue';
    import { useMoney } from "@/utils/useMoney";
    import { useSelect } from "@/utils/useSelects";
    import { Inertia } from '@inertiajs/inertia';

    const props = defineProps({
            budgets: {
                type: Array,
                required: true
            },
            categories: {
                type: Array,
                required: true
            }
    });

    const { sumMoney } = useMoney();
    const { categoryOptions: makeCategoryOptions }  = useSelect();

    const state = reactive({
        isModalOpen: false,
        isAddingGroup: false,
        form: useForm({
            account_id: null,
            parent_id: null,
            name: '',
            amount: 0,
        }),
        budgetTotal: computed(() => {
            return sumMoney(props.budgets.data.map(item => item.amount));
        }),
        categoryOptions: computed(() => {
            return makeCategoryOptions(props.categories, 'accounts', 'categoryOptions');
        })
    })

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

    const { budgetTotal, categoryOptions, isModalOpen, form, isAddingGroup } = toRefs(state);
</script>
