export type TransactionStatus = "draft" | "verified" | "planned";

export interface ITransaction {
    id: number;
    title: string;
    currencyCode: string;
    status: string;
    payee: IPayee;
    direction: TransactionStatus;
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
    reconciliations_last?: IReconciliation;
    account_detail_type_id: number;
    currency_code: string;
}

export interface IReconciliation {
    id: number;
    difference: number;
    amount: number;
    date: string;
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
