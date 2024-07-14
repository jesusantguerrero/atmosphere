import { format } from 'date-fns';
import { FREQUENCY_TYPE } from './../../utils/index';
// @ts-ignore
import ExactMath from "@/plugins/exactMath"
import { getFrequencyMonthFactor } from "./getFrequencyMonthFactor";
import { BudgetTarget, IBudgetCategory } from "./models/budget";
import { useDatePager } from "vueuse-temporals";

export enum InflowCategories {
    READY_TO_ASSIGN = "Ready to Assign"
}
export const isSpendingTarget = (budgetMetaData: Record<string, any>) => {
    return budgetMetaData.target_type == 'spending'
}

export const isSavingBalance = (budgetMetaData: Record<string, any>) => {
    return budgetMetaData.target_type == 'saving_balance'
}
export const getCategoriesTotals = (categories: Record<string, any>, config = {
    onOverspent: (category: IBudgetCategory) => {},
    onOverAssigned: (category: IBudgetCategory) => {},
    onUnderFunded: (category: IBudgetCategory) => {},
    onFunded: (category: IBudgetCategory) => {}
}) => {
    return Object.values(categories).reduce((categoryTotals, category) => {
        const { budget: budgetTarget } = category;
        categoryTotals.budgeted = ExactMath.add(categoryTotals.budgeted ?? 0, parseFloat(category.budgeted ?? 0))
        categoryTotals.activity =  ExactMath.add(categoryTotals.activity ?? 0, category.activity || 0)
        categoryTotals.available = ExactMath.add(categoryTotals.available ?? 0, category.available || 0)
        categoryTotals.budgetAvailable = ExactMath.add(categoryTotals.budgetAvailable ?? 0, category.name !== InflowCategories.READY_TO_ASSIGN ? (category.available || 0) : 0)
        categoryTotals.prevMonthLeftOver = ExactMath.add(categoryTotals.prevMonthLeftOver ?? 0, category.prevMonthLeftOver || 0)

        // credit cards
        categoryTotals.budgetedSpending = ExactMath.add(categoryTotals.budgetedSpending ?? 0, !category.account_id && category.name !== InflowCategories.READY_TO_ASSIGN  ? (category.activity || 0) : 0)
        categoryTotals.payments = ExactMath.add(categoryTotals.payments ?? 0, category.name !== InflowCategories.READY_TO_ASSIGN  ? category.payments : 0)
        categoryTotals.fundedSpending = ExactMath.add(categoryTotals.fundedSpending ?? 0, category.name !== InflowCategories.READY_TO_ASSIGN  ? category.funded_spending : 0)
        categoryTotals.fundedSpendingPreviousMonth = ExactMath.add(categoryTotals.fundedSpendingPreviousMonth || 0, category.account_id && category.name !== InflowCategories.READY_TO_ASSIGN  ? (category.available || 0): 0)

        if (Number(category.available) < 0 && category.name !== InflowCategories.READY_TO_ASSIGN) {
            category.budgeted
            config.onOverspent(category);
            categoryTotals.hasOverspent = true;
        }

        if (budgetTarget && isSpendingTarget(budgetTarget) && budgetTarget.amount) {
            const factor = getFrequencyMonthFactor(budgetTarget, budgetTarget.month)
            const monthlyTarget = ExactMath.mul(budgetTarget.amount, factor)
            const progress = ExactMath.add(category.budgeted ?? 0, category.left_from_last_month ?? 0);

            if (progress > monthlyTarget) {
                config.onOverAssigned(category)
                categoryTotals.hasOverAssigned = true;
            } else if (progress < monthlyTarget) {
                config.onUnderFunded(category)
                categoryTotals.hasUnderFunded = true;
            } else if (progress == monthlyTarget) {
                config.onFunded(category)
                categoryTotals.hasFunded = true;
            }

            categoryTotals.monthlyGoals.target = ExactMath.add(categoryTotals.monthlyGoals.target ?? 0, monthlyTarget ?? 0)
            categoryTotals.monthlyGoals.balance = ExactMath.add(categoryTotals.monthlyGoals.balance ?? 0, progress ?? 0)
        }

        return categoryTotals;
    }, {
        budgeted: 0,
        budgetAvailable: 0,
        activity: 0,
        available: 0,
        prevMonthLeftOver: 0,
        budgetedSpending: 0,
        payments: 0,
        fundedSpending: 0,
        fundedSpendingPreviousMonth: 0,
        monthlyGoals: {
            target: 0,
            balance: 0
        }
    })
}

export const getGroupTotals = (groups: Record<string, any>) => {
    return groups.reduce((groupTotals: Record<string, any>, group: Record<string, any>) => {
        groupTotals.budgeted = ExactMath.add(group.budgeted, groupTotals.budgeted || 0)
        groupTotals.activity = ExactMath.add(group.activity, groupTotals.activity || 0)
        groupTotals.budgetAvailable = ExactMath.add(group.budgetAvailable, groupTotals.budgetAvailable || 0)
        groupTotals.prevMonthLeftOver = ExactMath.add(group.prevMonthLeftOver, groupTotals.prevMonthLeftOver)

        groupTotals.available = ExactMath.add(group.available, groupTotals.available || 0)

        // credit cards calculations
        groupTotals.budgetedSpending = ExactMath.add(group.budgetedSpending, groupTotals.budgetedSpending || 0)
        groupTotals.payments = ExactMath.add(group.payments, groupTotals.payments || 0)
        groupTotals.fundedSpending= ExactMath.add(group.fundedSpending, groupTotals.fundedSpending || 0)
        groupTotals.fundedSpendingPreviousMonth= ExactMath.add(group.fundedSpendingPreviousMonth, groupTotals.fundedSpendingPreviousMonth || 0)



        groupTotals.monthlyGoals.target = ExactMath.add(groupTotals.monthlyGoals.target || 0, group.monthlyGoals.target || 0)
        groupTotals.monthlyGoals.balance = ExactMath.add(groupTotals.monthlyGoals.balance || 0, group.monthlyGoals.balance || 0)

        return groupTotals;
    }, {
        budgeted: 0,
        budgetedSpending: 0,
        budgetAvailable: 0,
        activity: 0,
        prevMonthLeftOver: 0,
        payments: 0,
        fundedSpending: 0,
        fundedSpendingPreviousMonth: 0,
        monthlyGoals: {
            target: 0,
            balance: 0
        }
    })
}


const getMonthInstanceCount = (frequency: string, weekDay: string, date = new Date()) => {

    const { selectedSpan } = useDatePager({ nextMode: 'month' });
    let timesMultiplier = 1
    try {
        timesMultiplier = frequency == FREQUENCY_TYPE.WEEKLY ?
        selectedSpan.value.filter((day: Date) => {
            return (
              format(day, "iiiiii").toLowerCase() ==
              weekDay?.toLowerCase()
            );
          })?.length: 1
    } catch (e) {
        console.log(selectedSpan, e);
    }

  return timesMultiplier
};

export const getBudgetTarget = (budgetTarget: BudgetTarget, date?: Date) => {
    const monthInstanceCount = getMonthInstanceCount(budgetTarget.frequency, budgetTarget.frequency_week_day, date)
    return budgetTarget.amount * monthInstanceCount
}
