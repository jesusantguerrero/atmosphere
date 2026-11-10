import { v4 } from 'uuid';
export const transactionDBToTransaction = (transactions: any[]) => {
    return transactions.map(transaction => ({
        id: transaction.id || v4(),
        date: transaction.date,
        title: transaction.description,
        subtitle: transaction.account?.name ? `${transaction.account?.name} -> ${transaction.category?.name}` : '',
        value: transaction.total,
        status: transaction.status,
        currencyCode: transaction.currency_code,
    }))
}
export const transactionLinesDBToTransaction = (transactions: any[]) => {
    return transactions.map(transaction => ({
        id: transaction.id || v4(),
        date: transaction.date,
        title: transaction.concept,
        subtitle: transaction.account_from?.name ? `${transaction.account_from?.name} -> ${transaction.category?.name}` : '',
        value: transaction.amount,
        status: transaction.status,
        currencyCode: transaction.currency_code,
    }))
}
export const draftsDBToTransaction = (transactions: any[]) => {
    return transactions.map(transaction => ({
        id: transaction.id || v4(),
        date: transaction.date,
        title: transaction.description,
        subtitle: transaction.linked ? `${transaction.linked}` : '',
        value: transaction.total,
        status: transaction.status,
        currencyCode: transaction.currency_code,
    }))
}

export const fromDBToAllAccounts = (transactions) => {
    return transactions.map(transaction => ({
        id: transaction.id || v4(),
        date: transaction.date,
        title: transaction.description,
        subtitle: transaction.category?.name,
        value: transaction.total,
        valueSubtitle: transaction.account?.name,
        status: 'VERIFIED',
        currencyCode: transaction.currency_code,
    }))
}

export const categoryDBToTransaction = (transactions) => {
    return transactions.map(transaction => ({
        id: transaction.category_id || v4(),
        date: transaction.date || '',
        title: transaction.name,
        subtitle: transaction?.account?.name ? `${transaction.account?.name} -> ${transaction.category?.name} hello` : '',
        value: transaction.total,
        status: 'VERIFIED',
        currencyCode: transaction.currency_code,
    }))
}

export const plannedDBToTransaction = (transactions) => {
    return transactions.map(transaction => ({
        id: transaction.id,
        date: transaction.date,
        title: transaction.description,
        subtitle: transaction.category?.name ?? '',
        value: transaction.total,
        status: 'PLANNED'
    }))
}

type Translate = (key: string, params?: Record<string, unknown>) => string;

/**
 * Human label for a planned transaction's schedule: «Mensual», «Semanal» or
 * «Cada 4 semanas». Null when the row has no frequency (not a recurring plan).
 */
export const recurrenceLabel = (frequency: string | null | undefined, interval: number | string | null | undefined, t: Translate): string | null => {
    if (!frequency) return null;
    const every = Math.max(1, Number(interval) || 1);
    const unit = frequency.toUpperCase();

    if (unit === 'WEEK_DAY') return t('Weekdays');
    if (unit === 'DAILY') return every === 1 ? t('Daily') : t('Every {count} days', { count: every });
    if (unit === 'WEEK' || unit === 'WEEKLY') return every === 1 ? t('Weekly') : t('Every {count} weeks', { count: every });
    if (unit === 'MONTHLY') return every === 1 ? t('Monthly') : t('Every {count} months', { count: every });

    return null;
};
