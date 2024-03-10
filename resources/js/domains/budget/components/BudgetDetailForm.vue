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
    import { BudgetTarget, getTargetName } from '../models/budget';

    const props = withDefaults(defineProps<{
        category: ICategory,
        item: BudgetTarget,
        parentId?: number;
        full?: boolean;
        hideDetails?: boolean,
        editable?: boolean;
        compact?: boolean
    }>(), {
        hideDetails: true,
        editable: true,
        compact: false,
    });


    const emit = defineEmits(['update:category', 'close', 'delete'])

    const form = useForm({
        category_id: null,
        parent_id: null,
        color: props.category.color || generateRandomColor(),
        name: props.category.name,
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
        () => props.item,
        (currentItem) => {
            if (currentItem) {
                form.reset()
                Object.keys(form.data()).forEach(key => {
                    // @ts-ignore: trust me
                    form[key] = currentItem[key] || form[key]
                })
                form.color = props.category.color ??  generateRandomColor();
                form.name = props.category.name;
            } else {
                form.reset()
            }
        },
        { deep: true, immediate: true });


    const onBlur = (categoryItem: ICategory) => {
        if (props.category.id) {
            window.axios.put(`/budgets/${props.category.id}?json=true`, categoryItem)
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
                            :disabled="compact"
                            v-model="category.color"
                            @update:modelValue="onBlur(category)"
                        />
                    </div>
                </template>
            </AtInput>
            <section>
                <span v-if="compact" class="capitalize">
                    {{ getTargetName(item.target_type) }}
                </span>
                <LogerButtonTab @click="$emit('close')" v-if="editable">
                    <IconClose />
                </LogerButtonTab>
            </section>
        </header>

        <BudgetTargetForm
            v-if="!compact"
            :parent-id="parentId"
            :item="item"
            :category="category"
            :editable="editable"
            :compact="compact"
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
                <BudgetMoneyLine title="Left from last month" :value="category.left_from_last_month" />
                <BudgetMoneyLine title="Assigned this month" :value="category.budgeted" />
                <BudgetMoneyLine title="Cash expending" :value="category.activity" />
                <BudgetMoneyLine title="Credit expending" :value="category.activity" />
            </section>

            <BudgetCategoryTransactions :category-id="category.id" v-else />
        </section>
    </section>
</template>
