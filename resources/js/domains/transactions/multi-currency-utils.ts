import { formatCurrency, getCurrencyByCode } from './currency-constants';

export interface CurrencyBalance {
  currency_code: string;
  balance: number;
  pending_balance?: number;
}

export interface ExchangeRateData {
  from_currency: string;
  to_currency: string;
  rate: number;
  date: string;
}

export interface MultiCurrencyTransaction {
  id?: number;
  amount: number;
  currency_code: string;
  exchange_rate?: number;
  exchange_amount?: number;
  converted_currency?: string;
  is_converted: boolean;
}

export const calculateExchangeRate = (total: number, exchangeAmount: number): number => {
  if (!total || !exchangeAmount) return 0;
  return exchangeAmount / total;
};

export const convertAmount = (amount: number, exchangeRate: number): number => {
  if (!amount || !exchangeRate) return 0;
  return amount * exchangeRate;
};

export const formatMultiCurrencyAmount = (
  amount: number, 
  originalCurrency: string, 
  convertedAmount?: number, 
  convertedCurrency?: string
): string => {
  const originalFormatted = formatCurrency(amount, originalCurrency);
  
  if (convertedAmount && convertedCurrency && convertedCurrency !== originalCurrency) {
    const convertedFormatted = formatCurrency(convertedAmount, convertedCurrency);
    return `${originalFormatted} (${convertedFormatted})`;
  }
  
  return originalFormatted;
};

export const validateExchangeRate = (rate: number): boolean => {
  return rate > 0 && rate < 1000; // Basic validation for reasonable exchange rates
};

export const getCurrencyBalanceDisplay = (balances: CurrencyBalance[]): string => {
  if (!balances || balances.length === 0) return '';
  
  return balances
    .map(balance => formatCurrency(balance.balance, balance.currency_code))
    .join(' | ');
};

export const getPendingBalanceDisplay = (balances: CurrencyBalance[]): string => {
  if (!balances || balances.length === 0) return '';
  
  return balances
    .filter(balance => balance.pending_balance && balance.pending_balance > 0)
    .map(balance => formatCurrency(balance.pending_balance!, balance.currency_code))
    .join(' | ');
};