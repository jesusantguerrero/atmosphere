export * from "./transactions";

export interface TransactionConfig {
    recurrence: boolean;
    automatic: boolean;
    transactionData: null | Record<string, any>
}
