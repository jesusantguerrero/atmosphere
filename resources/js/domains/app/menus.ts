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
        label: 'Reminders',
        url: '/housing/occurrence'
    },
    {
        label: 'Plans',
        url: '/housing/plans'
    },
    {
        label: 'Routine',
        url: '/housing/routine'
    },
    {
        label: 'Utilities',
        url: '/housing/utilities'
    },
    {
        label: 'People',
        url: '/loger-profiles'
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
            url: '/shopping'
        },
        {
            label: 'Templates',
            url: '/meals/menus/templates',
        },
    ],
    [MODULES.FINANCE]: [{
        label: 'Overview',
        url: '/finance'
    },
    {
        label: 'Budget',
        url: '/budgets'
    },
    {
        label: 'Goals',
        url: '/finance/goals'
    },
    {
        label: 'Planners',
        url: '/finance/planners/house-buyer'
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
        },
        {
            label: 'Relationships',
            url: '/trends/relationships',
            // Gated: page is a mock until backend hooks land. The
            // admin panel toggles this without a code deploy.
            featureFlag: 'trends-relationships',
        }
    ],  [MODULES.ADMIN]: [
        {
          label: "Overview",
          to: "/admin",
          isActiveFunction(currentPath: string) {
            return "/admin" == currentPath;
          },
        },
        {
          label: "Users",
          to: "/admin/users",
        },
        {
          label: "Teams",
          to: "/admin/teams",
        },
        {
          label: "Feature Flags",
          to: "/admin/feature-flags",
        },
        {
          label: "Mail",
          to: "/admin/mail",
        },
      ],
}


/**
 * Filter section menu items by:
 *   - explicit `hidden: true` (compile-time hide)
 *   - `featureFlag` (runtime toggle — checked against the shared
 *     `featureFlags` prop that HandleInertiaRequests populates).
 *
 * The activeFlags param is optional so callers that don't have Inertia
 * context (SSR, tests) still work; missing = all featureFlag items
 * hidden, which is the safe default.
 */
export const getSectionMenu = (sectionName, activeFlags: Record<string, boolean> = {}) => {
    return menus[sectionName].filter(item => {
        if (item.hidden) return false;
        if (item.featureFlag && !activeFlags[item.featureFlag]) return false;
        return true;
    });
}
