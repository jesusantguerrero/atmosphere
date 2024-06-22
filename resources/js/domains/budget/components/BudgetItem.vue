<script setup lang="ts">
import { format, startOfMonth, isThisMonth } from 'date-fns';
import { ref, nextTick, onMounted, inject, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { NDropdown } from 'naive-ui';
import autoAnimate from '@formkit/auto-animate';

import IconDrag from '@/Components/icons/IconDrag.vue';
import IconAllocated from '@/Components/icons/IconAllocated.vue';
import BalanceInput from "@/Components/atoms/BalanceInput.vue";
import BudgetTransaction from '@/Components/atoms/BudgetTransaction.vue';
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import ExpenseChartWidgetRow from '@/domains/transactions/components/ExpenseChartWidgetRow.vue';
import BudgetItemHeader from './BudgetItemHeader.vue';
import InputMoney from '@/Components/atoms/InputMoney.vue';

import formatMoney from "@/utils/formatMoney";
import { BudgetCategory } from '@/domains/budget/models/budget';
import { getBudgetTarget } from '@/domains/budget/budgetTotals';
import { ICategory } from '@/domains/transactions/models';

import { useAppContextStore } from '@/store';
import { useAccounts } from '@/utils/useAccounts';

const props = defineProps<{
    item: BudgetCategory;
    showDelete: boolean;
}>();

const emit = defineEmits(['removed', 'edit'])
const budgeted = ref<number>(props.item.budgeted);

const budgetTarget = computed(() => {
    return props.item.budget ? getBudgetTarget(props.item.budget) : 0;
})

const { accounts } = useAccounts();
const accountMatch = computed(() => {
    return props.item.match_account && accounts?.find?.((account: any ) => props.item.match_account.account_id == account.id);
});

const lastMonthAmount = computed(() => {
    // @ts-ignore:
    return props.item.budgets?.at?.(2)?.budgeted ?? 0
})

enum BudgetAssignOptions {
    Target = 'target',
    MatchAccount = 'matchAccount',
    Underfunded = 'underfunded',
    LastMonth = 'lastMonth'
}

const isCurrentMonth = computed(() => {
  return isThisMonth(startOfMonth(pageState.dates.endDate))
})

const assignOptions = computed(() => {
    const matchAccountDiffAmount = accountMatch.value?.balance - props.item.available;
    const underfundedAmount = budgetTarget.value - props.item.available;
    const options = [{
        name: BudgetAssignOptions.Target,
        label: `Set Target (${formatMoney(budgetTarget.value)})`,
        hidden: !budgetTarget.value
    },
    {
        name: BudgetAssignOptions.MatchAccount,
        label: `Set match account (${formatMoney(matchAccountDiffAmount)})`,
        hidden: !accountMatch.value || !isCurrentMonth.value || !matchAccountDiffAmount
    },
    {
        name: BudgetAssignOptions.Underfunded,
        label: `Set account match (${formatMoney(underfundedAmount)})`,
        hidden: !budgetTarget.value || !underfundedAmount
    },
    {
        name: BudgetAssignOptions.LastMonth,
        label: `Set last amount (${formatMoney(lastMonthAmount.value)})`,
        hidden: !lastMonthAmount.value
    },
    {
        name: "clear",
        label: "Clear",
    }]

    return options.filter((option) => !option.hidden)
});

const setAmount = (amount: number) => {
    budgeted.value = amount;
};

const handleAssignOptions = (option: string) => {
  const targetAmount = props.item.budget ? getBudgetTarget(props.item.budget) : 0;
switch (option) {
    case BudgetAssignOptions.Target:
      setAmount(targetAmount);
      break;
    case BudgetAssignOptions.Underfunded:
      setAmount(targetAmount - props.item.available);
      break;
    case BudgetAssignOptions.MatchAccount:
      setAmount(accountMatch.value?.balance - props.item.available);
      break;
    case BudgetAssignOptions.LastMonth:
      setAmount(lastMonthAmount.value);
      break;
    default:
      setAmount(0);
      break;
  }
  onAssignBudget()
};
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

const onAssignBudget = () => {
    nextTick(() => {
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
        isEditing.value = false;
    })
}

const pageState = inject('pageState', {});



const currentDetails = ref("");
const fetchDetails = async (category: ICategory) => {
    const startDate = format(pageState.dates.startDate, 'yyyy-MM-dd');
    const endDate = format(pageState.dates.endDate, 'yyyy-MM-dd');
    currentDetails.value = "";
    const response = await axios.get(`/api/category-transactions/${category.id}/details`, {
        params: {
            filter: {
                date: `${startDate}~${endDate}`
            },
        }
    })

    currentDetails.value = response.data?.transactions.data;
}

const inputContainer = ref()
onMounted(() => {
    autoAnimate(inputContainer.value)
})

const context = useAppContextStore();
</script>


<template>
<div class="px-4 py-2 cursor-pointer group" @click.stop="$emit('edit')">
    <section class="md:flex space-between">
        <div class="flex items-center w-full space-x-4 ">
            <div class="hidden mr-4 cursor-grab group-hover:inline-block">
                <IconDrag class="handle" />
            </div>
            <BudgetItemHeader :item="item" :show-delete="showDelete" />
        </div>
        <div class="flex items-center md:justify-end justify-between text-right flex-nowrap">
            <div ref="inputContainer"  title="Money Assigned" class="w-36">
                <InputMoney
                    ref="input"
                    class="opacity-100 cursor-text"
                    v-model="budgeted"
                    :number-format="true"
                    @blur="onAssignBudget()"
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
                :item="item"
                hide-title
                type="categories"
                classes="w-44 h-full"
                v-if="!context.isMobile"
                :details="currentDetails"
                @open-details="fetchDetails(item)"
            />
            <BalanceInput
                :value="item.available"
                :formatter="formatMoney"
                :category="item"
                class="flex items-center h-full w-28"
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

