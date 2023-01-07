export const MODULES = {
    HOUSING: 'housing',
    MEAL: 'meal',
    FINANCE: 'finance',
    RELATIONSHIP: 'relationship'
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
    },
     {
        label: 'Trends',
        url: '/trends'
    }]
}


export const getSectionMenu = (sectionName) => {
    return menus[sectionName].filter(item => !item.hidden)
}
