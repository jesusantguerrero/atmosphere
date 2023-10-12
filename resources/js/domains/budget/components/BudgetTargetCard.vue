<script setup lang="ts">
import { differenceInCalendarMonths, parseISO } from "date-fns";
import { computed } from "vue";

import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";

import BudgetProgress from "./BudgetProgress.vue";
import BudgetMoneyLine from "./BudgetMoneyLine.vue";

import { getBudgetTarget } from "@/domains/budget/budgetTotals";
import { isSavingBalance, isSpendingTarget } from "@/domains/budget";
import { BudgetTarget } from "@/domains/budget/models/budget";
import { ICategory } from "@/domains/transactions/models";
import { formatDate, formatMoney, formatMonth, toOrdinals } from "@/utils";
import LogerButton from "@/Components/atoms/LogerButton.vue";
// import IconPicker from '../IconPicker.vue';

const props = defineProps<{
    category: ICategory;
    item: BudgetTarget;
    editable: boolean;
    isProcessing: boolean;
}>();

defineEmits(["edit", "completed"]);

const targetDate = computed(() => {
    if (props.item.frequency_month_date == -1) {
        return 'End of month';
    } else if (props.item.frequency_month_date) {
        return `The ${toOrdinals(props.item.frequency_month_date)}`
    }
    return formatDate(props.item.frequency_date)
})

function instancesLeft() {
    return  !props.item.frequency_date ? 1 : differenceInCalendarMonths(parseISO(props.item.frequency_date), parseISO(props.category.month));
}

const monthlyContribution = computed(() => {
    if (isSpendingTarget(props.item)) {
        return  props.item.amount
    } else {
        return props.item.amount / instancesLeft();
    }
})

const currentAmount = computed(() => {
    if (isSpendingTarget(props.item)) {
        const { available, budgeted } = props.category;
        console.log(available, budgeted, props.item, props.category)
        return available > budgeted ? available : budgeted
    } else {
        return props.category.available;
    }
})
const monthlyTargetDiff = computed(() => {
    return monthlyContribution.value - currentAmount.value;
})

const isOnTrack = computed(() => monthlyTargetDiff.value <= 0)

const progressClass = computed(() => {
    return isOnTrack.value ? 'bg-success' : 'bg-warning';
})

const dividerClass = computed(() => {
    return isOnTrack.value ?  'bg-success/10 text-success border-success' : 'bg-warning/10 text-warning border-warning'
})

const fundedLabel = computed(() => isSpendingTarget(props.item) ? 'Funded' : 'Saved')

const targetAmount = computed(() => {
    return getBudgetTarget(props.item)
})

const targetTypeNames = {
    saving_balance: 'saving balance'
}

const getTargetName = (code: string) => {
    return targetTypeNames[code] ?? code;
}

const isGoal = computed(() => {
    return isSavingBalance(props.item);
})


</script>

<template>
<div class="w-full">
    <header class="flex justify-between w-full mb-8">
        <h4 class="text-lg font-bold text-primary">
            Target: <span class="capitalize"> {{ getTargetName(item.target_type) }} </span>
        </h4>
        <button class="text-secondary" @click="$emit('edit')" v-if="editable">
            Edit target
        </button>
    </header>
    <BudgetProgress
        class="h-1.5 rounded-sm"
        :goal="targetAmount"
        :current="currentAmount"
        :progress-class="[progressClass, 'bg-secondary/5']"
        :show-labels="false"
    >
    <template #before>
        <header class="mb-1 font-bold">{{ formatMoney(targetAmount) }} by {{ targetDate }}</header>
    </template>

    <template v-slot:after="{ progress }">
        <div class="flex justify-between w-full mt-1">
            <span>{{ progress}}% Funded ({{ formatMoney(currentAmount) }})</span>
            <span> Started {{ formatMonth(item.created_at, 'MMMM, yyyy') }}</span>
        </div>
    </template>
    </BudgetProgress>

    <footer class="mt-4">
        <div class="px-5 py-2 my-4 border-2 rounded-md " :class="dividerClass">
            <span v-if="!isOnTrack">
                Assign <MoneyPresenter :value="monthlyTargetDiff" /> more to reach your target
            </span>
            <span v-else>
                You're on track to reach your target!
            </span>
        </div>

        <BudgetMoneyLine title="Total needed" :value="item.amount" />
        <BudgetMoneyLine :title="fundedLabel" :value="category.available" />
        <BudgetMoneyLine title="To go" :value="item.amount - category.available" />

        <section class="border-t py-2 items-center justify-center flex">
            <LogerButton variant="secondary" v-if="isGoal" @click="$emit('completed', item)" :processing="isProcessing">
                Complete Goal
            </LogerButton>
        </section>
    </footer>
</div>
</template>
