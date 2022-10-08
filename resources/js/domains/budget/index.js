import { FREQUENCY_TYPE } from "@/utils";
import { format, parseISO } from "date-fns";
import ExactMath from "exact-math";
import { useDatePager } from "vueuse-temporals";

export * from "./budgetCols"
export * from "./useBudget"

export const isSpendingTarget = budgetMetaData => {
    return budgetMetaData.target_type == 'spending'
}

export const targetTypes = [
    {
        value: 'spending',
        label: 'Spending',
        description: `For groceries, holidays, vacations
        Fund up to an amount with the ability to spend from it along the way
        `,

    }, {
        value: 'saving_balance',
        label: 'Saving Balance',
        description: `down payments, emergency fund
        Save this amount over time and maintain the balance by replenishing any money spent
        `
    }, {
        value: 'savings_monthly',
        label: 'Savings Monthly',
        description: `For saving goals with unknown targets
        Contribute this amount every month until you disable this target
        `
    }, {
        value: 'debt_monthly_payment',
        label: 'Debt Monthly Payment',
        description: `Use for: Mortgage, student loans, auto loans, etc
        Budget for payments until you are debt free
        `
    }
];

export const getCategoriesTotals = (categories, config = {
    onOverspent: () => {},
    onOverAssigned: () => {},
    onUnderfunded: () => {}
}) => {
    return Object.values(categories).reduce((acc, category) => {
        const { budget: budgetTarget } = category;
        acc.budgeted = ExactMath.add(acc.budgeted, category.budgeted || 0)
        acc.activity =  ExactMath.add(acc.activity, category.activity || 0)
        acc.available = ExactMath.add(acc.available, category.available || 0)

        if (Number(category.available) < 0 && category.name !== 'Inflow') {
            config.onOverspent(category);
            category.budgeted
            acc.hasOverspent = true;
        }

        if (budgetTarget && isSpendingTarget(budgetTarget) && budgetTarget.amount) {
            const factor = getFrequencyMonthFactor(budgetTarget, budgetTarget.month)
            const monthlyTarget = ExactMath.mul(budgetTarget.amount, factor)

            if (parseFloat(category.budgeted) > monthlyTarget) {
                config.onOverAssigned(category)
                acc.hasOverAssigned = true;
                category.hasOverAssigned = true;
            } else if (parseFloat(category.budgeted) < monthlyTarget) {
                category.hasUnderfunded = true;
                config.onUnderfunded && config.onUnderfunded(category)
            }

            acc.monthlyGoals.target = ExactMath.add(acc.monthlyGoals.target, monthlyTarget)
            acc.monthlyGoals.balance = ExactMath.add(acc.monthlyGoals.balance, category.budgeted)
        }

        return acc;
    }, {
        budgeted: 0,
        activity: 0,
        available: 0,
        monthlyGoals: {
            target: 0,
            balance: 0
        }
    })
}

export const getGroupTotals = (groups) => {
    return groups.reduce((acc, group) => {
        acc.budgeted = ExactMath.add(group.budgeted, acc.budgeted || 0)
        acc.activity = ExactMath.add(group.activity, acc.activity || 0)
        acc.monthlyGoals.target = ExactMath.add(acc.monthlyGoals.target, group.monthlyGoals.target)
        acc.monthlyGoals.balance = ExactMath.add(acc.monthlyGoals.balance, group.monthlyGoals.balance)
        return acc;
    }, {
        budgeted: 0,
        activity: 0,
        monthlyGoals: {
            target: 0,
            balance: 0
        }
    })
}

export const getFrequencyMonthFactor = (recurrence, dateString) => {
    try {
        const date = typeof dateString == 'string' ? parseISO(dateString): dateString;
        const { selectedSpan } =  useDatePager({nextMode: 'month', initialDate: date })
        return recurrence.frequency == FREQUENCY_TYPE.WEEKLY
        ? selectedSpan.value.filter(
                day => {
                    return format(day, 'iiiiii').toLowerCase() == recurrence.frequency_week_day?.toLowerCase()
                }).length
        : 1;
    } catch (err) {
        return 1
    }
}
