export const MODULES = {
    HOUSING: 'housing',
    MEAL: 'meal',
    FINANCE: 'finance',
    RELATIONSHIP: 'relationship',
    TRENDS: 'trends',
    ADMIN: 'admin',
}

const menus = {
    // Household sub-nav intentionally reduced to the two surfaces that
    // get daily use. Overview, Plans, Routine, Utilities, People still
    // exist and are reachable by URL; they just don't crowd the top
    // bar. "Bills" is Occurrences (recurring utility/subscription bills).
    [MODULES.HOUSING]: [
        {
            label: 'Bills',
            url: '/housing/occurrence'
        },
        {
            label: 'Chores',
            url: '/housing/chores'
        },
    ],
    // Food sub-nav reduced to the two action surfaces: plan the week,
    // buy the ingredients. Overview, Recipes, Ingredients and Templates
    // still exist at their URLs and are reachable from inside Planner
    // (recipe pickers, template loaders) and Shopping List.
    [MODULES.MEAL]: [
        {
            label: 'Planner',
            url: '/meal-planner'
        },
        {
            label: 'Shopping List',
            url: '/shopping'
        },
    ],
    // Finance sub-nav reduced to Budget (intent / limits) + Accounts
    // (truth / balances). Overview, Goals, Planners, Watchlist,
    // Transactions, Reconciliation and Payees still exist. Reconciliation
    // in particular is one click deep: Accounts -> account page shows the
    // "Last reconciled · date" affordance which links to the history.
    [MODULES.FINANCE]: [
        {
            label: 'Budget',
            url: '/budgets'
        },
        {
            label: 'Accounts',
            url: '/finance/accounts'
        },
    ],
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
