import { v4 } from 'uuid';
export const transactionDBToTransaction = (transactions) => {
    return transactions.map(transaction => ({
        id: transaction.id || v4(),
        date: transaction.date,
        title: transaction.description,
        subtitle: transaction.account?.name ? `${transaction.account?.name} -> ${transaction.category?.name}` : '',
        value: transaction.total,
        status: 'VERIFIED',
        currencyCode: transaction.currency_code,
    }))
}

export const categoryDBToTransaction = (transactions) => {
    return transactions.map(transaction => ({
        id: transaction.category_id || v4(),
        date: transaction.date || '',
        title: transaction.name,
        subtitle: transaction?.account?.name ? `${transaction.account?.name} -> ${transaction.category?.name}` : '',
        value: transaction.total,
        status: 'VERIFIED',
        currencyCode: transaction.currency_code,
    }))
}

export const plannedDBToTransaction = (transactions) => {
    console.log(transactions, "data")
    return transactions.map(transaction => ({
        id: transaction.id,
        date: transaction.date,
        title: transaction.description,
        subtitle: `${transaction.account?.name} -> ${transaction.category?.name ?? ''} `,
        value: transaction.total,
        status: 'PLANNED'
    }))
}
