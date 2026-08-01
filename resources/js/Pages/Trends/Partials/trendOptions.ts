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
        label: 'Insights',
        url: '/trends',
    },
    {
        label: 'Net Worth',
        url: '/trends/net-worth',
    },
    {
        label: 'Credit Cards',
        url: '/trends/credit-cards',
    },
    {
        label: 'Financial Overview',
        url: '/trends/financial-overview',
    },
];
