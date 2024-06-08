<script setup lang="ts">
import { differenceInCalendarMonths, parseISO } from "date-fns";
import { computed, inject } from "vue";
// @ts-ignore: no definitions
import exactMath from "@/plugins/exactMath"

import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";

import BudgetProgress from "./BudgetProgress.vue";

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

const accountsOptions = inject("accountsOptions", []);


const account = computed(() => {
    return accountsOptions.find(account => props.item.account_id == account.id)
});

function instancesLeft() {
    return  !props.item.frequency_date
    ? 1
    : differenceInCalendarMonths(parseISO(props.item.frequency_date), parseISO(props.category.month));
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
        const { budgeted, left_from_last_month } = props.category;
        return exactMath.add(budgeted ?? 0, left_from_last_month ?? 0)
    } else {
        return props.category.available;
    }
})
const monthlyTargetDiff = computed(() => {
    return account.value.balance - currentAmount.value;
})

const isOnTrack = computed(() => monthlyTargetDiff.value <= 0)

const progressClass = computed(() => {
    return isOnTrack.value ? 'bg-success' : 'bg-warning';
})

const dividerClass = computed(() => {
    return isOnTrack.value ?  'bg-success/10 text-success border-success' : 'bg-warning/10 text-warning border-warning'
})
</script>

<template>
<div class="w-full">

    <BudgetProgress
        class="h-1.5 rounded-sm"
        :goal="account.balance"
        :current="currentAmount"
        :filled="category.activity"
        :progress-class="[progressClass, 'bg-secondary/5']"
        :show-labels="false"
    >
    <template #before>
        <header class="flex justify-between w-full">
            <section class="mb-1 font-bold">{{ formatMoney(account.balance) }}</section>
            <section class="flex items-center w-full justify-end space-x-1">
                <h4 class="font-bold text-primary">
                    <span class="capitalize"> {{ account.name }} </span>
                </h4>
                <button class="text-secondary" @click="$emit('edit')" v-if="editable">
                    <IMdiEdit/>
                </button>
            </section>
        </header>
    </template>

    <template v-slot:after="{ progress }">
        <div class="flex justify-between w-full mt-1">
            <span>{{ progress}}% Matched ({{ formatMoney(currentAmount) }})</span>
        </div>
    </template>
    </BudgetProgress>

    <footer class="mt-4">
        <div class="px-5 py-2 my-4 border-2 rounded-md " :class="dividerClass">
            <span v-if="!isOnTrack">
                Assign <MoneyPresenter :value="monthlyTargetDiff" /> more to match with account
            </span>
            <span v-else>
                Category matched with account!
            </span>
        </div>
    </footer>
</div>
</template>
