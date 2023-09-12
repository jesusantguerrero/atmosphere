<script setup lang="ts">
import { format, startOfMonth } from 'date-fns';
import { ref, nextTick, onMounted, inject } from 'vue';
import { router } from '@inertiajs/vue3';
import { NDropdown } from 'naive-ui';
import { computed } from 'vue';

import IconDrag from '@/Components/icons/IconDrag.vue';
import IconAllocated from '@/Components/icons/IconAllocated.vue';
import BalanceInput from "@/Components/atoms/BalanceInput.vue";
import BudgetTransaction from '@/Components/atoms/BudgetTransaction.vue';
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";

import { BudgetCategory } from '@/domains/budget/models/budget';
import autoAnimate from '@formkit/auto-animate';

import formatMoney from "@/utils/formatMoney";
import { getBudgetTarget } from '@/domains/budget/budgetTotals';
import ExpenseChartWidgetRow from '@/domains/transactions/components/ExpenseChartWidgetRow.vue';
import BudgetItemHeader from './BudgetItemHeader.vue';
import InputMoney from '@/Components/atoms/InputMoney.vue';
import { ICategory } from '@/domains/transactions/models';


const props = defineProps<{
    item: BudgetCategory;
    showDelete: boolean;
}>();

const emit = defineEmits(['removed', 'edit'])
const budgeted = ref<number>(props.item.budgeted);

const budgetTarget = computed(() => {
    return props.item.budget ? getBudgetTarget(props.item.budget) : 0;
})

const lastMonthAmount = computed(() => {
    // @ts-ignore:
    return props.item.budgets?.at?.(2)?.budgeted ?? 0
})

const assignOptions = computed(() => {
    return [{
        name: "setTarget",
        label: `Set Target (${formatMoney(budgetTarget.value)})`,
    },
    {
        name: "underFundedTarget",
        label: `Set underfunded (${formatMoney(budgetTarget.value - props.item.available)})`,
    },
    {
        name: "setLastMonth",
        label: `Set last amount (${formatMoney(lastMonthAmount.value)})`,
    },
    {
        name: "clear",
        label: "Clear",
    }]
});

const setAmount = (amount: number) => {
    budgeted.value = amount;
};

const handleAssignOptions = (option: string) => {
    const targetAmount = getBudgetTarget(props.item.budget);

    switch (option) {
    case "setTarget":
      setAmount(targetAmount);
      break;
    case "underFundedTarget":
      setAmount(targetAmount - props.item.available);
      break;
    case "setLastMonth":
      setAmount(lastMonthAmount.value);
      break;
    default:
      setAmount(0);
      break;
  }
  onAssignBudget()
};

const onAssignBudget = () => {
    if (Number(props.item.budgeted) !== Number(budgeted.value) && budgeted.value !== null) {
        const month = format(startOfMonth(pageState.dates.endDate), 'yyyy-MM-dd');

        router.post(`/budgets/${props.item.id}/months/${month}`, {
            id: props.item.id,
            budgeted: Number(budgeted.value),
            date: format(new Date(), 'yyyy-MM-dd')
        }, {
            preserveState: true,
            preserveScroll: true
        });
    }
    toggleEditing()
}


const removeCategory = () => {
    if (confirm("Are you sure you want to remove this category?")) {
        router.delete(`budgets/${props.item.id}`, {
            onSuccess() {
                emit('removed', props.item.id)
                router.reload({
                    only: ['budgets'],
                    preserveScroll: true,
                })
            }
        })
    }
}

const pageState = inject('pageState', {});
const updateActivity = () => {
    const month = format(startOfMonth(pageState.dates.endDate), 'yyyy-MM-dd');

    router.put(`budgets/${props.item.id}/months/${month}`, {
        onSuccess() {
            router.reload({
                only: ['budgets'],
                preserveScroll: true,
            })
        }
    })
}

const isEditing = ref(false);
const input = ref()
const toggleEditing = () => {
    isEditing.value = !isEditing.value
    if (isEditing.value) {
        nextTick(() => {
            input.value.focus()
        })
    }
}

const currentDetails = ref("");
const fetchDetails = async (category: ICategory) => {
    const startDate = format(pageState.dates.startDate, 'yyyy-MM-dd');
    const endDate = format(pageState.dates.endDate, 'yyyy-MM-dd');

    const response = await axios.get(`/api/category-transactions/${category.id}/details`, {
        params: {
            filter: {
                date: `${startDate}~${endDate}`
            },
        }
    })


    category.details = response.data?.transactions.at(0).details;
}

const inputContainer = ref()
onMounted(() => {
    autoAnimate(inputContainer.value)
})
</script>


<template>
<div class="px-4 py-2 cursor-pointer group" @click.stop="$emit('edit')">
    <section class="flex  space-between">
        <div class="flex items-center w-full space-x-4">
            <div class="mr-4 cursor-grab hidden group-hover:inline-block">
                <IconDrag class="handle" />
            </div>
            <BudgetItemHeader :item="item" :show-delete="showDelete" />
        </div>
        <div class="flex justify-end items-center text-right flex-nowrap">
            <div ref="inputContainer"  title="Money Assigned" class="w-36">
                <InputMoney
                    ref="input"
                    class="opacity-100 cursor-text"
                    v-model="budgeted"
                    :number-format="true"
                    @blur="onAssignBudget"
                    @click="toggleEditing"
                >
                    <template #prefix>
                        <IconAllocated class="text-lg text-success" />
                    </template>
                    <template #suffix>
                        <NDropdown
                            trigger="click"
                            :options="assignOptions"
                            key-field="name"
                            :on-select="handleAssignOptions"
                        >
                            <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
                        </NDropdown>
                    </template>
                </InputMoney>
            </div>
            <ExpenseChartWidgetRow
                :value="item.activity"
                hide-title
                :item="item"
                type="categories"
                classes="w-44 h-full"
                :details="currentDetails"
                @open-details="fetchDetails(item)"
            />
            <BalanceInput
                :value="item.available"
                :formatter="formatMoney"
                :category="item"
                class="h-full items-center flex w-28"
            >
                <template #suffix v-if="item.available">
                    <BudgetTransaction
                        :data="item.budget"
                        :category="item"
                        icon-only
                    />
                </template>
            </BalanceInput>
        </div>
    </section>
</div>
</template>

