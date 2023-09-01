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
import LogerInput from '@/Components/atoms/LogerInput.vue';
import PointAlert from '@/Components/atoms/PointAlert.vue';

import { BudgetCategory } from '@/domains/budget/models/budget';
import autoAnimate from '@formkit/auto-animate';

import formatMoney from "@/utils/formatMoney";
import { getBudgetTarget } from '@/domains/budget/budgetTotals';
import { getCategoryLink } from '@/domains/transactions/models/transactions';


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
    if (Number(props.item.budgeted) !== Number(budgeted.value)) {
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

const options = [{
    name: 'delete',
    label: 'Delete'
}, {
    name: 'hide',
    label: 'Hide'
}, {
    name: 'updateActivity',
    label: 'Update Activity'
}, {
    name: 'transactions',
    label: 'Transactions'
}]

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

const handleOptions = (option: string) => {
    switch(option) {
        case 'delete':
            removeCategory()
            break;
        case 'updateActivity':
            updateActivity()
            break;
        case 'transactions':
            const link = getCategoryLink(props.item.id, 'categories');
            router.visit(link)
        default:
            break;
    }
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

const inputContainer = ref()
onMounted(() => {
    autoAnimate(inputContainer.value)
})
</script>


<template>
<div class="flex px-4 py-2 cursor-pointer space-between" @click.stop="$emit('edit')">
    <div class="flex items-center w-full space-x-4">
        <button v-if="showDelete" class="text-gray-400 transition cursor-pointer hover:text-red-400 focus:outline-none" @click="$emit('deleted', $event)">
            <i class="fa fa-trash"></i>
        </button>
        <div class="mr-4 cursor-grab">
            <IconDrag class="handle" />
        </div>
        <div ref="inputContainer">
            <h4 class="flex cursor-pointer" @click="$emit('open')">
                <span class="items-center font-bold text-body-1">
                    <span :style="{ color: item.color }">
                        {{ item.name }}
                    </span>
                </span>
                <PointAlert
                    v-if="item.hasOverspent || item.hasUnderFunded"
                />
            </h4>
            <div class="flex items-center" title="Money Assigned">
                <LogerInput
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
                </LogerInput>
            </div>
        </div>
    </div>
    <div class="flex items-center space-x-2 text-right flex-nowrap min-w-fit">
        <BalanceInput
            :value="item.available"
            :formatter="formatMoney"
            :category="item"
        >
            <template #suffix v-if="item.available">
                <BudgetTransaction
                    :data="item.budget"
                    :category="item"
                    icon-only
                />
            </template>
        </BalanceInput>
        <NDropdown trigger="click" :options="options" key-field="name" :on-select="handleOptions" >
            <LogerButtonTab> <i class="fa fa-ellipsis-v"></i></LogerButtonTab>
        </NDropdown>
    </div>
</div>
</template>

