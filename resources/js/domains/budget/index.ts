import { BudgetTarget } from "./models/budget"

export * from "./budgetCols"
export * from "./useBudget"
export * from "./budgetTotals"
export * from "./getFrequencyMonthFactor"

export const isSpendingTarget = (budgetMetaData : BudgetTarget) => {
    return budgetMetaData.target_type == 'spending'
}

export const isSavingBalance = (budgetMetaData : BudgetTarget) => {
    return budgetMetaData.target_type == 'saving_balance'
}

export enum BudgetTargetTypes {
    Spending = 'spending',
    SavingBalance = 'saving_balance',
    SavingMonthly = 'saving_monthly',
    DebtMonthlyPayment = 'saving_monthly',
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

export const budgetFrequencies = [
    {
      value: "MONTHLY",
      label: "Monthly",
    },
    {
      value: "WEEKLY",
      label: "Weekly",
    },
    {
      value: "DATE",
      label: "By Date",
    },
];

