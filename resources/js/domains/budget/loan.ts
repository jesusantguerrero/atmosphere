import { differenceInCalendarMonths, parseISO } from "date-fns";

/**
 * Loan target math. A loan target stores the borrowed amount (principal), its
 * annual interest rate (percent) and the term in months; the monthly payment
 * lives in the target's `amount` so the normal MONTHLY funding logic treats it
 * as an obligation. These helpers derive the payment and the payoff schedule
 * (remaining balance, payments made/left) entirely on the client.
 */

export interface LoanTerms {
    principal: number;
    annualRatePct: number;
    termMonths: number;
    startDate?: string | Date | null;
}

const num = (v: any) => Number(v ?? 0) || 0;

/** Standard amortized monthly payment. Handles 0% as straight-line. */
export const loanMonthlyPayment = (principal: number, annualRatePct: number, termMonths: number): number => {
    const P = num(principal);
    const n = Math.max(0, Math.round(num(termMonths)));
    if (P <= 0 || n <= 0) return 0;
    const r = num(annualRatePct) / 100 / 12;
    if (r === 0) return P / n;
    const factor = Math.pow(1 + r, n);
    return (P * r * factor) / (factor - 1);
};

/** Outstanding balance after `paymentsMade` payments on the schedule. */
export const loanRemainingBalance = (
    principal: number,
    annualRatePct: number,
    termMonths: number,
    paymentsMade: number
): number => {
    const P = num(principal);
    const n = Math.max(0, Math.round(num(termMonths)));
    const k = Math.min(Math.max(0, Math.round(num(paymentsMade))), n);
    if (P <= 0 || n <= 0) return 0;
    const r = num(annualRatePct) / 100 / 12;
    const pmt = loanMonthlyPayment(P, annualRatePct, n);
    if (r === 0) return Math.max(0, P - pmt * k);
    const factor = Math.pow(1 + r, k);
    return Math.max(0, P * factor - pmt * ((factor - 1) / r));
};

/** Scheduled payments elapsed since the loan start, capped at the term. */
export const loanPaymentsMade = (target: Partial<LoanTerms>, asOf: Date = new Date()): number => {
    const n = Math.max(0, Math.round(num(target.termMonths)));
    if (!target.startDate || n <= 0) return 0;
    const start = target.startDate instanceof Date ? target.startDate : parseISO(String(target.startDate).slice(0, 10));
    if (isNaN(start.getTime())) return 0;
    return Math.min(Math.max(0, differenceInCalendarMonths(asOf, start)), n);
};

export interface LoanPayoff {
    monthlyPayment: number;
    paymentsMade: number;
    paymentsRemaining: number;
    remainingBalance: number;
    paidPrincipal: number;
    percentPaid: number;
    /** Schedule progress by payment count (matches the "k/n paid" label). */
    percentByPayments: number;
}

const termsFromTarget = (t: any): LoanTerms => ({
    principal: num(t?.principal),
    annualRatePct: num(t?.interest_rate),
    termMonths: Math.round(num(t?.term_months)),
    startDate: t?.loan_start_date ?? null,
});

export const getLoanPayoff = (target: any, asOf: Date = new Date()): LoanPayoff => {
    const { principal, annualRatePct, termMonths, startDate } = termsFromTarget(target);
    const monthlyPayment = loanMonthlyPayment(principal, annualRatePct, termMonths);
    const paymentsMade = loanPaymentsMade({ termMonths, startDate }, asOf);
    const remainingBalance = loanRemainingBalance(principal, annualRatePct, termMonths, paymentsMade);
    const paidPrincipal = Math.max(0, principal - remainingBalance);
    return {
        monthlyPayment,
        paymentsMade,
        paymentsRemaining: Math.max(0, termMonths - paymentsMade),
        remainingBalance,
        paidPrincipal,
        percentPaid: principal > 0 ? Math.min(100, (paidPrincipal / principal) * 100) : 0,
        percentByPayments: termMonths > 0 ? Math.min(100, (paymentsMade / termMonths) * 100) : 0,
    };
};

export const isLoanTarget = (t: any): boolean => t?.target_type === "loan";
