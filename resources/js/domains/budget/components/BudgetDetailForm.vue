<script setup lang="ts">
    import { watch , ref } from 'vue';
    // @ts-ignore
    import { AtInput } from "atmosphere-ui";
    import { useForm } from '@inertiajs/vue3';

    import ColorSelector from '@/Components/molecules/ColorSelector.vue';
    import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';
    import IconClose from '@/Components/icons/IconClose.vue';
    import SectionNav from '@/Components/molecules/SectionNav.vue';

    import BudgetTargetForm from './BudgetTargetForm.vue';
    import BudgetMoneyLine from './BudgetMoneyLine.vue';
    import BudgetCategoryTransactions from './BudgetCategoryTransactions.vue';

    import { generateRandomColor } from "@/utils"
    import { ICategory } from '@/Modules/transactions/models/transactions';

    const { hideDetails = true , category , item , editable = true } = defineProps<{
        parentId: number;
        full: boolean;
        category: ICategory,
        item: Record<string, string>,
        hideDetails: boolean,
        editable: boolean;
    }>();

    const emit = defineEmits(['update:category'])

    const form = useForm({
        category_id: null,
        parent_id: null,
        color: category.color || generateRandomColor(),
        name: category.name,
        amount: 0,
        assigned: 0,
        target_type: '',
        frequency: 'MONTHLY',
        frequency_month_date: null,
        frequency_week_day: null,
        frequency_date: null,
        frequency_interval: 0,
        frequency_interval_unit: 0,
    })

    watch(
        () => item,
        (currentItem) => {
            if (currentItem) {
                form.reset()
                Object.keys(form.data()).forEach(key => {
                    // @ts-ignore: trust me
                    form[key] = currentItem[key] || form[key]
                })
            } else {
                form.reset()
            }
        },
        { deep: true, immediate: true });


    const onBlur = (categoryItem: ICategory) => {
        if (category.id) {
            window.axios.put(`/budgets/${category.id}?json=true`, categoryItem)
            .then(() => {
                emit('update:category', categoryItem)
            })
        }
    }

    const activeTab = ref('monthDetail');
    const tabs = [{
            label:'Month Detail',
            value: 'monthDetail'
        },
        {
            label: 'Transactions',
            value: 'transactions'
        }
    ];
</script>


<template>
    <section class="px-5 pt-2 pb-4 space-y-4 text-left border-b rounded-md shadow-xl bg-base-lvl-3" :class="{'flex': !full }">
        <header class="flex items-center justify-between mt-2 mb-2">
            <AtInput
                v-model="category.name"
                @blur="onBlur(category)"
                class="border-transparent cursor-pointer "
                rounded
                :class="{'hover:text-primary hover:border-primary': editable}"
                :disabled="!editable"
            >
                <template #prefix>
                    <div class="flex items-center justify-center px-1 ">
                        <ColorSelector
                            v-model="category.color"
                            @update:modelValue="onBlur(category)"
                        />
                        {{  category.id }}
                    </div>
                </template>
            </AtInput>
            <LogerButtonTab @click="$emit('close')" v-if="editable">
                <IconClose />
            </LogerButtonTab>
        </header>

        <BudgetTargetForm
            :parent-id="parentId"
            :category="category"
            :item="item"
            :editable="editable"
            @delete="$emit('delete')"
        />

        <div v-if="false">
            Auto-Assign
        </div>


        <section v-if="!hideDetails">
            <SectionNav v-model="activeTab" :sections="tabs" />
            <section v-if="activeTab == 'monthDetail'">
                Available Balance
                <BudgetMoneyLine title="Left Over from last month" :value="category.prevMonthLeftOver" />
                <BudgetMoneyLine title="Assigned this month" :value="category.budgeted" />
                <BudgetMoneyLine title="Cash expending" :value="category.activity" />
                <BudgetMoneyLine title="Credit expending" :value="category.activity" />
            </section>
    
            <BudgetCategoryTransactions :category-id="category.id" v-else />
          </section>
    </section>
</template>
