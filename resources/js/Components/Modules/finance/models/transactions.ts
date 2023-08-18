export interface ITransaction {
    id: number;
    title: string;
    currencyCode: string;
}

export interface ITransactionLine {
    id: number;
    category_id: number;
    title: string;
    currencyCode: string;
}

export interface ICategory {
    id?: number;
    name: string;
    color: string;
}
