export const MODULES = {
    HOUSING: 'housing',
    MEAL: 'meal',
    FINANCE: 'finance',
    RELATIONSHIP: 'relationship',
    TRENDS: 'trends'
}

const menus = {
    [MODULES.HOUSING]: [{
        label: 'Overview',
        url: '/housing'
    },
    {
        label: 'Chores',
        url: '/housing/chores'
    },
    {
        label: 'Occurrence Checks',
        url: '/housing/occurrence'
    },
    {
        label: 'Plans',
        url: '/housing/plans'
    },
    {
        label: 'Equipment',
        url: '/housing/equipments'
    }],
    [MODULES.MEAL]: [
        {
            label: 'Overview',
            url: '/meals/overview'
        },
        {
            label: 'Planner',
            url: '/meal-planner'
        }, {
            label: 'Recipes',
            url: '/meals'
        },
        {
            label: 'Ingredients',
            url: '/ingredients'
        }, {
            label: 'Menus',
            url: '/meals/menus',
            hidden: true
    }],
    [MODULES.FINANCE]: [{
        label: 'Overview',
        url: '/finance'
    },
    {
        label: 'Budget & Goals',
        url: '/budgets'
    },
    {
        label: 'Watchlist',
        url: '/finance/watchlist'
    },
    {
        label: 'Transactions',
        url: '/finance/transactions'
    }],
    [MODULES.TRENDS]: [
        {
            label: 'Spending',
            url: '/trends'
        },
        {
            label: 'Net Worth',
            url: '/trends/net-worth'
        },
        {
            label: 'Income v Expenses',
            url: '/trends/income-expenses'
        },
        {
            label: 'Income vs Expenses Graph',
            url: '/trends/income-expenses-graph'
        },
        {
            label: 'Year summary',
            url: '/trends/year-summary'
        }]
}


export const getSectionMenu = (sectionName) => {
    return menus[sectionName].filter(item => !item.hidden)
}
