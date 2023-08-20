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

export const getCategoryLink = (itemId: number, type: 'categories' | 'groups' ) => {
    const types = {
        categories: 'category_id',
        groups: 'group_id'
    }

    const itemField = types[type] ?? types.groups;
    const currentSearch = location.search.replace('?', '&');
    return `/finance/lines?${itemField}=${itemId}${currentSearch}`;
}
