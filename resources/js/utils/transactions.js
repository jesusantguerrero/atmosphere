export const transactionDBToTransaction = (transactions) => {
    return transactions.map(transaction => ({
        id: transaction.id,
        date: transaction.date,
        title: transaction.description,
        subtitle: transaction.account.name ? `${transaction.account.name} -> ${transaction.category.name}` : '',
        value: transaction.total,
        status: 'VERIFIED'
    }))
}
