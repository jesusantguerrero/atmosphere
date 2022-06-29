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
