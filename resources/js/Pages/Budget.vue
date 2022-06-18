<template>
    <app-layout>
        <template #header>
            <div class="flex justify-between px-10">
                <div>
                <h2 class="text-xl font-bold leading-tight text-pink-400">
                    Budget settings
                </h2>

                </div>

                <div>
                    <AtButton class="h-10 text-white bg-pink-400" @click="isAddingGroup=true" rounded>
                        Add Budget Group
                    </AtButton>
                </div>
            </div>
        </template>

        <div class="mx-auto text-gray-200 rounded-lg shadow-lg bg-slate-600 max-w-7xl ">
            <div class="flex">
                <div class="space-y-3" :class="[!selectedBudget ? 'w-full': 'w-7/12']" >
                <NDataTable
                    :columns="budgetCols(state)"
                    :data="state.budget"
                    :row-key="({ id }) => id"
                    children-key="subCategories"
                    flex-height
                />
                </div>
                <section class="py-5 text-center" :class="[selectedBudget ? 'w-5/12' : 'd-none']" v-if="selectedBudget">
                    <BudgetItemForm
                        class="mt-5"
                        full
                        :category="selectedBudget"
                        :item="selectedBudget.budget"
                        @saved="onBudgetItemSaved"
                        @deleted="deleteBudget"
                        @cancel="selectedBudget = null"
                    />
                </section>
            </div>
        </div>

        <category-modal
            @close="isModalOpen=false"
            v-model:show="isModalOpen"
        />
    </app-layout>
</template>

<script setup>
    import AppLayout from '@/Layouts/AppLayout';
    import { AtButton, AtField, AtInput, AtErrorBag } from "atmosphere-ui";
    import CategoryModal from '../Components/CategoryModal.vue';
    import { useForm } from '@inertiajs/inertia-vue3';
    import { NSelect, NDataTable } from "naive-ui"
    import { computed, h, reactive, toRefs } from 'vue';
    import BudgetItem from '../Components/molecules/BudgetItem.vue';
    import LogerInput from '../Components/atoms/LogerInput.vue';
    import { useMoney } from "@/utils/useMoney";
    import { useSelect } from "@/utils/useSelects";
    import { Inertia } from '@inertiajs/inertia';
    import BudgetItemForm from '../Components/molecules/BudgetItemForm.vue';
    import { format, startOfMonth } from 'date-fns';
    import ExactMath from "exact-math";
    import formatMoney from '../utils/formatMoney';
    import { budgetCols } from "@/domains/budget/budgetCols"

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
        isAddingGroup: true,
        expandedCategory: null,
        selectedBudget: null,
        form: useForm({
            account_id: null,
            parent_id: null,
            name: '',
            amount: 0,
        }),
        budgetTotal: computed(() => {
            return 0 // sumMoney(props.budgets.data.map(item => item.amount));
        }),
        categoryOptions: computed(() => {
            return makeCategoryOptions(props.categories, 'accounts', 'categoryOptions');
        }),
        budget: computed(() => {
            return props.budgets.data.map(item => {
                item.budgeted = Object.values(item.subCategories).reduce((acc, cur) => ExactMath.add(acc, cur.budgeted || 0), 0);
                item.spent = Object.values(item.subCategories).reduce((acc, cur) => ExactMath.add(acc, cur.spent || 0), 0);
                item.available = Object.values(item.subCategories).reduce((acc, cur) => ExactMath.add(acc, cur.available || 0), 0);
                return item
            });
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
        state.form.transform((data) => ({
            ...data,
            parent_id: state.expandedCategory ? state.expandedCategory.id : null
        })).post('/budgets', {
            onSuccess: () => {
                state.form.reset();
            }
        })
    }

    const { budgetTotal, categoryOptions, isModalOpen, form, isAddingGroup, selectedBudget } = toRefs(state);
</script>
