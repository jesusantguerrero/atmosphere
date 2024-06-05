export const MODULES = {
    HOUSING: 'housing',
    MEAL: 'meal',
    FINANCE: 'finance',
    RELATIONSHIP: 'relationship',
    TRENDS: 'trends',
    ADMIN: 'admin',
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
    }
    ],
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
        },
        {
            label: 'Shopping List',
            url: '/shopping-list'
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
        }
    ],  [MODULES.ADMIN]: [
        {
          label: "Dashboard",
          to: "/admin",
          isActiveFunction(currentPath: string) {
            return "/admin" == currentPath;
          },
        },
        {
          label: "Teams",
          to: "/admin/teams",
        },
        {
          label: "Subscriptions",
          to: "/admin/subscriptions",
        },
        {
          label: "Users",
          to: "/admin/users",
        },
        {
          label: "Commands",
          to: "/admin/commands",
        },
        {
          label: "Settings",
          to: "/admin/settings/email",
        },
        {
          label: "Backups",
          to: "/admin/backups",
        },
        {
          label: "Pulse",
          url: "/pulse",
          as: 'a'
        },
      ],
}


export const getSectionMenu = (sectionName) => {
    return menus[sectionName].filter(item => !item.hidden)
}
