<template>
<div class="w-full">
    <header class="justify-between flex w-full mb-8">
        <h4 class="text-primary font-bold text-lg">
            Target
            <p> {{item.target_type}} </p>
        </h4>
        <button class="text-secondary" @click="$emit('edit')">
            Edit target
        </button>
    </header>
    <BudgetProgress
        class="h-1.5 rounded-sm"
        :goal="item.amount"
        :current="currentAmount"
        :progress-class="[progressClass, 'bg-secondary/5']"
        :show-labels="false"
    >
    <template #before>
        <header class="mb-1 font-bold">{{ item.amount }} by {{ targetDate }}</header>
    </template>

    <template v-slot:after="{ progress }">
        <div class="justify-between w-full flex mt-1">
            <span>{{ progress}}% Funded </span>
            <span> Started {{ formatMonth(item.created_at, 'MMMM, yyyy') }}</span>
        </div>
    </template>
    </BudgetProgress>

    <footer class="mt-4">
        <div class=" border-2 rounded-md py-2 px-5 my-4" :class="dividerClass">
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
    </footer>
</div>
</template>

<script setup>
import { isSpendingTarget } from "@/domains/budget";
import { formatDate, formatMonth, toOrdinals } from "@/utils";
import { differenceInCalendarMonths, format, parseISO } from "date-fns";
import { computed } from "vue";
import BudgetProgress from "./BudgetProgress.vue";
import MoneyPresenter from "./MoneyPresenter.vue";
import BudgetMoneyLine from "./BudgetMoneyLine.vue";
// import IconPicker from '../IconPicker.vue';

const props = defineProps({
  category: {
    type: Object,
    required: true,
  },
  item: {
    type: Object,
    default: () => {},
  },
});

const emit = defineEmits(["edit"]);

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
        console.log( instancesLeft())
        return props.item.amount / instancesLeft();
    }
})

const currentAmount = computed(() => {
    if (isSpendingTarget(props.item)) {
        const { available, budgeted } = props.category;
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
</script>
