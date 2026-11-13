<script setup lang="ts">
import { computed, ref } from 'vue';
import InfoHint from '@/Components/atoms/InfoHint.vue';
import formatMoney from '@/utils/formatMoney';

const props = defineProps<{
    accounts?: any[];
    budgets?: any[] | Record<string, any>;
    readyToAssign?: { balance?: number } | null;
    uncategorizedTotals?: { currency_code: string; total: number | string; count: number }[];
    /** Balances by account id at the close of a past month; null for the current month (live balances). */
    accountBalances?: Record<number, number | string> | null;
    /** Rendered inside the Ready-to-assign hero: no box of its own. */
    embedded?: boolean;
}>();

const baseCurrency = computed(() => (window as any)?.logerAppSettings?.currency_code || 'DOP');

// On-budget cash = not a credit card (credit_closing_day) and not a
// resource-backed tracking account (resource_type_id). Those are the balances
// the identity reconciles against (cards enter via their payment category).
const cashByCurrency = computed<Record<string, number>>(() => {
    const out: Record<string, number> = {};
    (props.accounts || []).forEach((a: any) => {
        if (!a || a.credit_closing_day || a.resource_type_id) return;
        const cur = a.currency_code || baseCurrency.value;
        const balance = props.accountBalances ? props.accountBalances[a.id] ?? 0 : a.balance;
        out[cur] = (out[cur] || 0) + Number(balance || 0);
    });
    return out;
});

const assignedByCurrency = computed<Record<string, number>>(() => {
    const out: Record<string, number> = {};
    const add = (cur: string, v: any) => { out[cur] = (out[cur] || 0) + Number(v || 0); };
    const accountCur = (id: number) =>
        (props.accounts || []).find((a: any) => a.id === id)?.currency_code || baseCurrency.value;
    Object.values((props.budgets as any)?.data || props.budgets || {}).forEach((group: any) => {
        (group?.subCategories || []).forEach((c: any) => {
            if (!c || c.name === 'Ready to Assign') return;
            add(c.account_id ? accountCur(c.account_id) : baseCurrency.value, c.available);
        });
    });
    add(baseCurrency.value, props.readyToAssign?.balance);
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

const uncategorizedHref = (currency: string): string =>
    `/finance/transactions?filter[uncategorized]=1&filter[currency_code]=${encodeURIComponent(currency)}`;

const gapRows = computed(() => rows.value.filter((r) => Math.abs(r.uncategorized) >= 0.01));
const uncategorizedFor = (currency: string) =>
    (props.uncategorizedTotals || []).find((total) => total.currency_code === currency);

/** The full identity (Available + To assign vs cash) stays one click away instead of always on screen. */
const showCalculation = ref(false);
</script>

<template>
    <div
        v-if="rows.length || uncategorizedTotals?.length"
        :class="embedded ? '' : 'px-4 py-2 mt-2 rounded-lg border border-body-1/15 bg-base-lvl-2'"
    >
        <div v-if="!hasGap" class="flex items-center gap-2 text-xs font-medium text-success">
            <i class="fa fa-check-circle" aria-hidden="true" />
            {{ $t('Your budget matches your accounts') }}
        </div>
        <div v-else class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
            <span v-for="r in gapRows" :key="r.cur" class="text-body-1">
                {{ $t('Difference') }} <b class="text-body tabular-nums">{{ formatMoney(r.uncategorized, r.cur) }}</b>
                <a
                    v-if="uncategorizedFor(r.cur)"
                    :href="uncategorizedHref(r.cur)"
                    class="font-medium text-primary hover:underline"
                >· {{ $t('{count} uncategorized', { count: uncategorizedFor(r.cur)?.count }) }} →</a>
            </span>
            <InfoHint :title="$t('Difference')" :body="$t('reconcile_difference_hint')" />
            <button
                type="button"
                class="text-body-1/70 hover:text-body underline-offset-2 hover:underline md:ml-auto"
                :aria-expanded="showCalculation"
                @click="showCalculation = !showCalculation"
            >
                {{ showCalculation ? $t('Hide calculation') : $t('Show calculation') }}
            </button>
        </div>
        <div v-if="hasGap && showCalculation" class="mt-2 space-y-1 border-t border-body-1/15 pt-2">
            <div
                v-for="r in rows"
                :key="r.cur"
                class="flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs"
            >
                <span class="text-body-1">
                    {{ $t('Available') }} <b class="text-body tabular-nums">{{ formatMoney(r.assigned - (r.cur === baseCurrency ? Number(readyToAssign?.balance || 0) : 0), r.cur) }}</b>
                    + {{ $t('To assign') }} <b class="text-body tabular-nums">{{ formatMoney(r.cur === baseCurrency ? Number(readyToAssign?.balance || 0) : 0, r.cur) }}</b>
                    = <b class="text-body tabular-nums">{{ formatMoney(r.assigned, r.cur) }}</b>
                </span>
                <span class="text-body-1">vs</span>
                <span class="text-body-1">
                    {{ $t('Cash in accounts') }}
                    <b class="text-body tabular-nums">{{ formatMoney(r.cash, r.cur) }}</b>
                </span>
                <span v-if="Math.abs(r.uncategorized) >= 0.01" class="font-medium tabular-nums text-body-1 md:ml-auto">{{ $t('Difference') }} {{ formatMoney(r.uncategorized, r.cur) }}</span>
            </div>
        </div>
        <div v-if="uncategorizedTotals?.length && (!hasGap || showCalculation)" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 border-t border-body-1/15 pt-2">
            <a v-for="total in uncategorizedTotals" :key="total.currency_code" :href="uncategorizedHref(total.currency_code)" class="text-xs font-medium text-primary hover:underline">{{ formatMoney(total.total, total.currency_code) }} {{ $t('Uncategorized') }} · {{ total.count }} {{ $t('txns') }} →</a>
        </div>
    </div>
</template>
