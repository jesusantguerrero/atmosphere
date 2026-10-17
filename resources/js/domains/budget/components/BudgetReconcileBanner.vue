<script setup lang="ts">
import { computed } from 'vue';
import { format } from 'date-fns';
import formatMoney from '@/utils/formatMoney';

// Zero-sum reconciliation (Fase 1, read-only). Per currency:
//   Σ Available (every category, credit-card payment categories included)
//     + Ready to Assign  =  cash in on-budget accounts
// The difference is uncategorized spend — invisible in the table today — and
// links to those transactions. Multi-currency is bucketed per currency and
// never converted, matching .planning/budget-cuadre.md.
const props = defineProps<{
    accounts?: any[];
    budgets?: any[] | Record<string, any>;
    readyToAssign?: { balance?: number } | null;
    startDate?: any;
    endDate?: any;
}>();

const baseCurrency = (window as any)?.logerAppSettings?.currency_code || 'DOP';

// On-budget cash = not a credit card (credit_closing_day) and not a
// resource-backed tracking account (resource_type_id). Those are the balances
// the identity reconciles against (cards enter via their payment category).
const cashByCurrency = computed<Record<string, number>>(() => {
    const out: Record<string, number> = {};
    (props.accounts || []).forEach((a: any) => {
        if (!a || a.credit_closing_day || a.resource_type_id) return;
        const cur = a.currency_code || baseCurrency;
        out[cur] = (out[cur] || 0) + Number(a.balance || 0);
    });
    return out;
});

const assignedByCurrency = computed<Record<string, number>>(() => {
    const out: Record<string, number> = {};
    const add = (cur: string, v: any) => { out[cur] = (out[cur] || 0) + Number(v || 0); };
    const accountCur = (id: number) =>
        (props.accounts || []).find((a: any) => a.id === id)?.currency_code || baseCurrency;
    Object.values((props.budgets as any)?.data || props.budgets || {}).forEach((group: any) => {
        (group?.subCategories || []).forEach((c: any) => {
            if (!c || c.name === 'Ready to Assign') return;
            add(c.account_id ? accountCur(c.account_id) : baseCurrency, c.available);
        });
    });
    add(baseCurrency, props.readyToAssign?.balance);
    return out;
});

const rows = computed(() => {
    const curs = new Set<string>([
        ...Object.keys(cashByCurrency.value),
        ...Object.keys(assignedByCurrency.value),
    ]);
    return [...curs]
        .map((cur) => {
            const cash = cashByCurrency.value[cur] || 0;
            const assigned = assignedByCurrency.value[cur] || 0;
            const uncategorized = Math.round((assigned - cash) * 100) / 100;
            return { cur, cash, assigned, uncategorized };
        })
        .filter((r) => Math.abs(r.assigned) > 0.009 || Math.abs(r.cash) > 0.009);
});

const hasGap = computed(() => rows.value.some((r) => Math.abs(r.uncategorized) >= 0.01));

const fmtDate = (d: any) => {
    try { return d ? format(new Date(d), 'yyyy-MM-dd') : null; } catch (e) { return null; }
};
const uncategorizedHref = computed(() => {
    const s = fmtDate(props.startDate);
    const e = fmtDate(props.endDate);
    const date = s && e ? `&filter[date]=${s}~${e}` : '';
    return `/finance/transactions?filter[uncategorized]=1${date}`;
});
</script>

<template>
    <div
        v-if="rows.length"
        class="px-4 py-3 mt-3 rounded-lg border border-body-1/15 bg-base-lvl-2"
    >
        <div v-if="!hasGap" class="flex items-center gap-2 text-sm font-medium text-success">
            <i class="fa fa-check-circle" aria-hidden="true" />
            {{ $t('Your budget matches your accounts') }}
        </div>
        <div v-else class="space-y-1.5">
            <div
                v-for="r in rows"
                :key="r.cur"
                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm"
            >
                <span class="text-body-1">
                    {{ $t('Available + To assign') }}
                    <b class="text-body tabular-nums">{{ formatMoney(r.assigned, r.cur) }}</b>
                </span>
                <span class="text-body-1">vs</span>
                <span class="text-body-1">
                    {{ $t('Cash in accounts') }}
                    <b class="text-body tabular-nums">{{ formatMoney(r.cash, r.cur) }}</b>
                </span>
                <a
                    v-if="Math.abs(r.uncategorized) >= 0.01"
                    :href="uncategorizedHref"
                    class="font-semibold text-error hover:underline tabular-nums md:ml-auto"
                >{{ formatMoney(r.uncategorized, r.cur) }} {{ $t('Uncategorized') }} &rarr;</a>
            </div>
        </div>
    </div>
</template>
