import { format } from 'date-fns';
import { FREQUENCY_TYPE } from './../../utils/index';
// @ts-ignore
import ExactMath from "exact-math";
import { getFrequencyMonthFactor } from "./getFrequencyMonthFactor";
import { BudgetTarget } from "./models/budget";
import { useDatePager } from "vueuse-temporals";

export const isSpendingTarget = (budgetMetaData: Record<string, any>) => {
    return budgetMetaData.target_type == 'spending'
}

export const isSavingBalance = (budgetMetaData: Record<string, any>) => {
    return budgetMetaData.target_type == 'saving_balance'
}
export const getCategoriesTotals = (categories: Record<string, any>, config = {
    onOverspent: (category: Record<string, any>) => {},
    onOverAssigned: (category: Record<string, any>) => {},
    onUnderFunded: (category: Record<string, any>) => {},
    onFunded: (category: Record<string, any>) => {}
}) => {
    return Object.values(categories).reduce((categoryTotals, category) => {
        const { budget: budgetTarget } = category;
        categoryTotals.budgeted = ExactMath.add(categoryTotals.budgeted, category.budgeted || 0)
        categoryTotals.activity =  ExactMath.add(categoryTotals.activity, category.activity || 0)
        categoryTotals.available = ExactMath.add(categoryTotals.available, category.available || 0)
        categoryTotals.prevMonthLeftOver = ExactMath.add(categoryTotals.prevMonthLeftOver, category.prevMonthLeftOver || 0)

        if (Number(category.available) < 0 && category.name !== 'Inflow') {
            category.budgeted
            config.onOverspent(category);
            categoryTotals.hasOverspent = true;
        }

        if (budgetTarget && isSpendingTarget(budgetTarget) && budgetTarget.amount) {
            const factor = getFrequencyMonthFactor(budgetTarget, budgetTarget.month)
            const monthlyTarget = ExactMath.mul(budgetTarget.amount, factor)
            const budgetedAmount = parseFloat(category.budgeted);

            if (budgetedAmount > monthlyTarget) {
                config.onOverAssigned(category)
                categoryTotals.hasOverAssigned = true;
            } else if (budgetedAmount < monthlyTarget) {
                config.onUnderFunded(category)
                categoryTotals.hasUnderFunded = true;
            } else if (budgetedAmount == monthlyTarget) {
                config.onFunded(category)
                categoryTotals.hasFunded = true;
            }

            categoryTotals.monthlyGoals.target = ExactMath.add(categoryTotals.monthlyGoals.target, monthlyTarget)
            categoryTotals.monthlyGoals.balance = ExactMath.add(categoryTotals.monthlyGoals.balance, category.budgeted)
        }

        return categoryTotals;
    }, {
        budgeted: 0,
        activity: 0,
        available: 0,
        prevMonthLeftOver: 0,
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
        groupTotals.prevMonthLeftOver = ExactMath.add(group.prevMonthLeftOver, groupTotals.prevMonthLeftOver)
        groupTotals.monthlyGoals.target = ExactMath.add(groupTotals.monthlyGoals.target, group.monthlyGoals.target)
        groupTotals.monthlyGoals.balance = ExactMath.add(groupTotals.monthlyGoals.balance, group.monthlyGoals.balance)
        return groupTotals;
    }, {
        budgeted: 0,
        activity: 0,
        prevMonthLeftOver: 0,
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
