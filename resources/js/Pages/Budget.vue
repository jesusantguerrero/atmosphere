<template>
    <AppLayout>
        <template #header>
            <div class="flex justify-between px-10">
                <div class="flex items-center">
                    <h2 class="text-xl font-bold leading-tight text-pink-400">
                        Budget settings
                    </h2>
                    <span class="text-slate-200 ml-2"> {{ readyToAssign }} </span>
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
                    :data="budgetState"
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
    </AppLayout>
</template>

<script setup>
    import { computed, reactive, toRefs } from 'vue';
    import { Inertia } from '@inertiajs/inertia';
    import { useForm } from '@inertiajs/inertia-vue3';
    import { AtButton } from "atmosphere-ui";
    import ExactMath from "exact-math";
    import { NDataTable } from "naive-ui"
    import AppLayout from '@/Layouts/AppLayout.vue';
    import CategoryModal from '@/Components/CategoryModal.vue';
    import { useMoney } from "@/utils/useMoney";
    import { useSelect } from "@/utils/useSelects";
    import BudgetItemForm from '@/Components/molecules/BudgetItemForm.vue';
    import { budgetCols, useBudget } from "@/domains/budget"

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

    const { categoryOptions: makeCategoryOptions }  = useSelect();

    const { budgets } = toRefs(props)
    const { readyToAssign, budgetState } = useBudget(budgets)

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

    const { isModalOpen, isAddingGroup, selectedBudget } = toRefs(state);
</script>
