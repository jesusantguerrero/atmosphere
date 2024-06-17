export interface MonthStat {
    total: number,
    currency_code: string,
    lastTransactionDate: string,
    transactionsCount: number
}

export interface WatchlistResource {
    month: MonthStat;
    prevMonth: MonthStat;
    transactions: Record<string, {
        data: Record<string, string>[]
    }>
}
