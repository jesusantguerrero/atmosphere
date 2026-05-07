<script setup lang="ts">
import { format, startOfMonth, isThisMonth } from 'date-fns';
import { ref, nextTick, onMounted, inject, computed } from 'vue';
import { NDropdown } from 'naive-ui';
import { router, usePage } from '@inertiajs/vue3';
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

const emit = defineEmits(['removed', 'edit', 'assign', 'move']);
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

const categoryAverages = computed<Record<number, number>>(() => {
    return (usePage().props.categoryAverages || {}) as Record<number, number>
})

const averageAmount = computed<number>(() => {
    const raw = categoryAverages.value[props.item.id]
    return raw ? Number(raw) : 0
})

const showAveragePill = computed<boolean>(() => {
    return averageAmount.value > 0 && Number(props.item.budgeted) === 0
})

const spentPercent = computed(() => {
    const budgeted = Number(props.item.budgeted) || 0;
    if (budgeted <= 0) {
        return null;
    }
    const activity = Math.abs(Number(props.item.activity) || 0);
    return Math.min(100, Math.round((activity / budgeted) * 100));
});

const overspentPercent = computed(() => {
    const budgeted = Number(props.item.budgeted) || 0;
    if (budgeted <= 0) {
        return 0;
    }
    const activity = Math.abs(Number(props.item.activity) || 0);
    return activity > budgeted ? Math.min(100, Math.round(((activity - budgeted) / budgeted) * 100)) : 0;
});

const progressBarClass = computed(() => {
    const pct = spentPercent.value;
    if (pct === null) {
        return '';
    }
    if (overspentPercent.value > 0) {
        return 'bg-error';
    }
    if (pct >= 90) {
        return 'bg-warning';
    }
    return 'bg-success';
});

enum BudgetAssignOptions {
    Target = 'target',
    MatchAccount = 'matchAccount',
    Underfunded = 'underfunded',
    LastMonth = 'lastMonth',
    Average = 'average',
    SetSavingsDefault = 'set_savings_default',
    SetSpendingDefault = 'set_spending_default',
    ClearDefaultRole = 'clear_default_role',
}

const isCurrentMonth = computed(() => {
  return isThisMonth(startOfMonth(pageState.dates.endDate))
})

const currentDefaultRole = computed<string | null>(() => {
    const defaults = (usePage().props.budgetDefaults || {}) as Record<string, number>
    if (defaults.savings === props.item.id) {
        return 'savings'
    }
    if (defaults.spending === props.item.id) {
        return 'spending'
    }
    return null
})

const assignOptions = computed(() => {
    const matchAccountDiffAmount = accountMatch.value?.balance - props.item.available;
    const underfundedAmount = budgetTarget.value - props.item.available;
    const role = currentDefaultRole.value
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
        name: BudgetAssignOptions.Average,
        label: `Set 3-month avg (${formatMoney(averageAmount.value)})`,
        hidden: !averageAmount.value
    },
    {
        name: 'clear',
        label: 'Clear',
    },
    { type: 'divider', key: 'divider-1' } as any,
    {
        name: BudgetAssignOptions.SetSavingsDefault,
        label: 'Use as default Savings target',
        hidden: role === 'savings',
    },
    {
        name: BudgetAssignOptions.SetSpendingDefault,
        label: 'Use as default Spending target',
        hidden: role === 'spending',
    },
    {
        name: BudgetAssignOptions.ClearDefaultRole,
        label: 'Remove default role',
        hidden: role === null,
    }]

    return options.filter((option) => !option.hidden)
});

const setAmount = (amount: number) => {
    budgeted.value = amount;
};

const useAverage = () => {
    setAmount(averageAmount.value);
    onAssignBudget();
};

const setDefaultRole = (role: string | null) => {
    router.patch(`/budgets/${props.item.id}/default-role`, { role }, {
        preserveScroll: true,
        preserveState: true,
        only: ['budgetDefaults'],
    })
}

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
    case BudgetAssignOptions.Average:
      setAmount(averageAmount.value);
      break;
    case BudgetAssignOptions.SetSavingsDefault:
      setDefaultRole('savings');
      return;
    case BudgetAssignOptions.SetSpendingDefault:
      setDefaultRole('spending');
      return;
    case BudgetAssignOptions.ClearDefaultRole:
      setDefaultRole(null);
      return;
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

// On mobile the side panel covers the input and the on-screen keyboard fights
// for space; tapping the row should focus the inline input instead. Desktop
// keeps the row→panel pattern.
const onRowClick = () => {
    if (context.isMobile) {
        toggleEditing();
        return;
    }
    emit('edit');
}

const pageState = inject('pageState', {});

const currentMonth = computed(() => {
    return format(startOfMonth(pageState.dates.endDate), 'yyyy-MM-dd');
})

const onAssignBudget = () => {
    nextTick(() => {
        if (Number(props.item.budgeted) !== Number(budgeted.value) && budgeted.value !== null) {
            emit('assign', {
                month: currentMonth.value,
                budgeted: Number(budgeted.value),
            })
        }
        isEditing.value = false;
    })
}

const onMoveFromBudget = (movementData: any) => {
    emit('move', {
        ...movementData,
        date: currentMonth.value,
    })
}

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
<div class="px-4 py-2 cursor-pointer group" @click.stop="onRowClick">
    <section class="md:flex space-between">
        <div class="flex items-center w-full space-x-4 ">
            <!-- On mobile (no hover state), show the drag handle always so reorder is
                 discoverable. Desktop keeps the hover-reveal pattern. -->
            <div class="mr-4 cursor-grab inline-block md:hidden">
                <IconDrag class="handle" />
            </div>
            <div class="hidden mr-4 cursor-grab md:group-hover:inline-block">
                <IconDrag class="handle" />
            </div>
            <BudgetItemHeader :item="item" :show-delete="showDelete" />
        </div>
        <div class="flex items-center md:justify-end justify-between text-right flex-nowrap">
            <div ref="inputContainer" title="Money Assigned" class="w-36" @click.stop>
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
                <button
                    v-if="showAveragePill"
                    type="button"
                    class="mt-1 px-2 py-0.5 text-[11px] leading-none rounded-full bg-primary/10 text-primary hover:bg-primary/20 transition"
                    :title="$t('Click to use this amount')"
                    @click.stop="useAverage"
                >
                    {{ $t('Avg') }}: {{ formatMoney(averageAmount) }}
                </button>
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
                @move="onMoveFromBudget"
                class="flex items-center h-full w-28"
                :class="Number(item.available) < 0 ? 'text-error font-semibold' : ''"
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
    <div
        v-if="spentPercent !== null"
        class="relative w-full h-1 mt-1 overflow-hidden rounded-sm bg-base-lvl-2"
        :title="`${spentPercent}% spent`"
    >
        <div class="absolute inset-y-0 left-0" :class="progressBarClass" :style="{ width: spentPercent + '%' }" />
    </div>
</div>
</template>

