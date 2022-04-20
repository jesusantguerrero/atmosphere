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
                    <AtField label="Parent Category" v-if="state.expandedCategory">
                        <div class="font-bold py-3">
                            {{ state.expandedCategory.name}}
                        </div>
                    </AtField>
                    <AtField label="Category" class="w-3/12" v-if="state.expandedCategory">
                        <AtInput v-model="form.name"/>
                        <AtErrorBag v-if="errors" :errors="errors" field="account_id" />
                    </AtField>
                    <AtField label="Account" class="w-3/12" v-if="categories.length">
                        <NSelect
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
                        <AtErrorBag v-if="errors" :errors="errors" field="account_id" />
                    </AtField>

                    <AtField label="Amount" class="w-4/12">
                        <AtInput type="number" v-model="form.amount" />
                        <AtErrorBag v-if="errors" errors="errors" field="amount" />
                    </AtField>

                    <div>
                        <AtField label="Action">
                            <at-button class="block h-full text-white bg-pink-500" @click="submit()"> Save </at-button>
                        </AtField>
                    </div>
                </div>

                <div class="flex">
                    <div class="px-5 pt-2 pb-10 space-y-3" :class="[!selectedBudget ? 'w-full': 'w-7/12']" >
                            <NDataTable
                                :columns="state.cols"
                                :data="budgets.data"
                                :row-key="({ id }) => id"
                                flex-height
                        />
                        <BudgetItem
                            class="font-bold"
                            :item="{
                                name: 'Totals',
                                amount: budgetTotal
                            }"
                        />
                    </div>
                    <section class="text-center py-5" :class="[selectedBudget ? 'w-5/12' : 'd-none']" v-if="selectedBudget">
                        <SectionTitle>
                            {{ selectedBudget.name }}
                        </SectionTitle>
                        <BudgetItemForm
                            class="mt-5"
                            full
                            :item="selectedBudget"
                            @saved="onBudgetItemSaved"
                            @cancel="selectedBudget = null"
                        />
                    </section>
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
    import { NSelect, NDataTable } from "naive-ui"
    import { computed, h, reactive, toRefs } from 'vue';
    import BudgetItem from '../Components/molecules/BudgetItem.vue';
    import { useMoney } from "@/utils/useMoney";
    import { useSelect } from "@/utils/useSelects";
    import { Inertia } from '@inertiajs/inertia';
    import BudgetItemForm from '../Components/molecules/BudgetItemForm.vue';
    import SectionTitle from '@/Components/atoms/SectionTitle.vue';

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
        cols: [
        {
            type: 'selection',
            fixed: 'left'
        },
        {
            title: 'Category',
            key: 'name',
            render (row, index) {
                const tag = !row.parent_id ? 'strong' : 'span';
                return h(tag, [
                        row.name,
                        h(AtButton, {
                            onclick: () => {
                                state.selectedBudget = row;
                            }},
                        'Edit'),
                        !row.parent_id && h(AtButton, {
                            onclick: () => {
                                state.expandedCategory = row;
                            }},
                            'Add subcategory'
                        )])
            }
        },
        {
            title: 'Assigned',
            key: 'amount',
        },
        {
            title: 'Activity',
            key: 'activity',
            render (row, index) {
                return h('span', ['row ', index])
            }
        },
        {
            title: 'Available',
            key: 'available',
            render (row, index) {
                return h('span', ['row ', index])
            }
        }],
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
