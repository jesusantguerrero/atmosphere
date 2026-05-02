export interface TrendSubTab {
    label: string;
    url: string;
}

export interface TrendOption {
    label: string;
    url?: string;
    subTabs?: TrendSubTab[];
}

export const trendOptions: TrendOption[] = [
    {
        label: 'Cash flow',
        subTabs: [
            { label: 'Spending', url: '/trends' },
            { label: 'Income', url: '/trends/payees' },
        ],
    },
    {
        label: 'Net Worth',
        url: '/trends/net-worth',
    },
    {
        label: 'Income v Expenses',
        subTabs: [
            { label: 'Table', url: '/trends/income-expenses' },
            { label: 'Graph', url: '/trends/income-expenses-graph' },
        ],
    },
    {
        label: 'Year',
        subTabs: [
            { label: 'Spending', url: '/trends/spending-year' },
            { label: 'Assigned', url: '/trends/assigned-year' },
        ],
    },
    {
        label: 'Credit Cards',
        url: '/trends/credit-cards',
    },
    {
        label: 'Financial Overview',
        url: '/trends/financial-overview',
    },
    {
        label: 'Relationships',
        url: '/trends/relationships',
    },
];
