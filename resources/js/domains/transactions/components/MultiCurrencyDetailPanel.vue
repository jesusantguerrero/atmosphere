<script setup lang="ts">
import { computed } from 'vue';
import { getCurrencyByCode, formatCurrency } from '../currency-constants';

/**
 * BHD-style "Detalle tarjeta de crédito" panel.
 *
 * Renders one column per currency, side-by-side, mirroring how dual-balance
 * cards (BHD, Banreservas, etc.) present their statement summary.
 *
 * Backed by `Account::getAllCurrencyBalances()`. Optional enrichment fields
 * (creditLimit, lastPayment, paymentsMonth) come from a future Account API
 * extension — the panel degrades gracefully when they're missing.
 */

export interface CurrencyDetail {
    currency_code: string;
    balance: number;
    pending_balance: number;
    total_balance: number;
    is_primary?: boolean;
    /** Optional enrichment — leave undefined and the row hides itself. */
    credit_limit?: number;
    available?: number;
    payments_month?: number;
    last_payment_amount?: number;
    last_payment_date?: string;
    extra_cupo?: number;
}

interface Props {
    accountName?: string;
    accountType?: 'credit_card' | 'bank' | 'savings' | string;
    currencies: CurrencyDetail[];
    /** If true, hide rows that no currency reports (clean look for non-CC accounts). */
    autoHideMissingRows?: boolean;
    /** Localizable labels per row — override in i18n later if needed. */
    labels?: Partial<typeof DEFAULT_LABELS>;
}

const DEFAULT_LABELS = {
    title: 'Detalle de la cuenta',
    creditLimit: 'Límite de crédito',
    available: 'Balance disponible para avance',
    paymentsMonth: 'Pagos del mes',
    lastPaymentAmount: 'Monto último pago',
    lastPaymentDate: 'Fecha último pago',
    currentBalance: 'Balance actual',
    extraCupo: 'Extra cupo',
};

const props = withDefaults(defineProps<Props>(), {
    accountType: 'credit_card',
    autoHideMissingRows: true,
    labels: () => ({}),
});

const L = computed(() => ({ ...DEFAULT_LABELS, ...(props.labels ?? {}) }));

const isCreditCard = computed(() => props.accountType === 'credit_card');

/** Format helpers — fall back to a hyphen when value is null/undefined. */
const fmt = (value: number | undefined, currency: string) => {
    if (value == null || Number.isNaN(value)) return '—';
    return formatCurrency(value, currency);
};

const currencyLabel = (code: string) => {
    const c = getCurrencyByCode(code);
    if (!c) return code;
    // BHD uses "PESOS" / "DÓLARES" — fall back to the name uppercased.
    return (c.name ?? code).toUpperCase();
};

/** Row definitions. Each describes one statement metric and how to read it from a currency. */
type RowDef = {
    key: string;
    label: string;
    pick: (c: CurrencyDetail) => number | string | undefined;
    isMoney?: boolean;
    isCreditOnly?: boolean;
};

const ROWS: RowDef[] = [
    { key: 'creditLimit', label: 'creditLimit', pick: c => c.credit_limit, isMoney: true, isCreditOnly: true },
    { key: 'available', label: 'available', pick: c => c.available, isMoney: true, isCreditOnly: true },
    // Use total_balance so both primary and secondary currencies show a real
    // number. Primary's total_balance == vendor `balance` accessor. Secondary
    // currencies have `balance` = 0 (the cache field) but accumulate activity
    // in `pending_balance`, so their total_balance reflects the actual saldo.
    // Previously we showed two rows (Balance actual + Pendiente sin facturar)
    // mimicking BHD's statement, but Loger doesn't track billing-cycle state
    // for secondary currencies — the "Pendiente" row just duplicated the
    // total under a misleading label that promised a distinction we don't
    // actually make.
    { key: 'currentBalance', label: 'currentBalance', pick: c => c.total_balance, isMoney: true },
    { key: 'paymentsMonth', label: 'paymentsMonth', pick: c => c.payments_month, isMoney: true, isCreditOnly: true },
    { key: 'lastPaymentAmount', label: 'lastPaymentAmount', pick: c => c.last_payment_amount, isMoney: true },
    { key: 'lastPaymentDate', label: 'lastPaymentDate', pick: c => c.last_payment_date, isMoney: false },
    { key: 'extraCupo', label: 'extraCupo', pick: c => c.extra_cupo, isMoney: true, isCreditOnly: true },
];

/** Filter rows the user shouldn't see for this account, or rows no currency populates. */
const visibleRows = computed(() => {
    return ROWS.filter(row => {
        if (row.isCreditOnly && !isCreditCard.value) return false;
        if (!props.autoHideMissingRows) return true;
        return props.currencies.some(c => row.pick(c) !== undefined && row.pick(c) !== null);
    });
});
</script>

<template>
    <section class="rounded-lg border border-base-lvl-2 bg-base-lvl-3 overflow-hidden">
        <header class="flex items-center justify-between px-5 py-4 border-b border-base-lvl-2">
            <div>
                <h3 class="text-base font-semibold text-body">
                    <slot name="title">{{ L.title }}</slot>
                </h3>
                <p v-if="accountName" class="text-xs text-body-1/70 mt-0.5">{{ accountName }}</p>
            </div>
            <slot name="actions" />
        </header>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-base-lvl-2">
                        <th class="text-left text-xs font-medium text-body-1/70 uppercase tracking-wider px-5 py-3 w-[40%]"></th>
                        <th
                            v-for="c in currencies"
                            :key="c.currency_code"
                            class="text-right text-xs font-semibold text-body-1 uppercase tracking-wider px-5 py-3"
                            :class="{ 'bg-primary/5': c.is_primary }"
                        >
                            {{ currencyLabel(c.currency_code) }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in visibleRows"
                        :key="row.key"
                        class="border-b border-base-lvl-2 last:border-0 hover:bg-base-lvl-2/40 transition-colors"
                    >
                        <td class="text-left text-body-1 px-5 py-2.5">{{ L[row.label] }}:</td>
                        <td
                            v-for="c in currencies"
                            :key="c.currency_code + row.key"
                            class="text-right tabular-nums font-medium text-body px-5 py-2.5"
                            :class="{ 'bg-primary/5': c.is_primary }"
                        >
                            <template v-if="row.isMoney">
                                {{ fmt(row.pick(c) as number, c.currency_code) }}
                            </template>
                            <template v-else>
                                {{ row.pick(c) ?? '—' }}
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <footer v-if="$slots.footer" class="px-5 py-3 border-t border-base-lvl-2 bg-base-lvl-2/40">
            <slot name="footer" />
        </footer>
    </section>
</template>
