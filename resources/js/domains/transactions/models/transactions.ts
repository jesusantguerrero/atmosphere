export interface ITransaction {
    id: number;
    title: string;
    currencyCode: string;
    status: string;
    payee: IPayee;
    direction: string;
    total: number;
    currency_code: string;
    description: string;
    linked: Record<string, any>[]
}

export interface ITransactionLine {
    id: number;
    category_id: number;
    title: string;
    currencyCode: string;
    is_split: boolean;
}

export interface ICategory {
    id?: number;
    name: string;
    color: string;
}
export interface IAccount {
    id: number;
    name: string;
    color: string;
    balance: number;
    reconciliations_pending?: IReconciliation
}

export interface IReconciliation {
    id: number;
    difference: number
}
export interface IPayee {
    id: number;
    name: string;
}

export const getCategoryLink = (itemId: number, type: 'categories' | 'groups' ) => {
    const types = {
        categories: 'category_id',
        groups: 'group_id'
    }

    const itemField = types[type] ?? types.groups;
    const currentSearch = location.search.replace('?', '&');
    return `/finance/lines?${itemField}=${itemId}${currentSearch}`;
}
