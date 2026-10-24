<script setup lang="ts">
import { computed } from 'vue';
import formatMoney from '@/utils/formatMoney';

const props = defineProps<{
    accounts?: any[];
    budgets?: any[] | Record<string, any>;
    readyToAssign?: { balance?: number } | null;
    uncategorizedTotals?: { currency_code: string; total: number | string; count: number }[];
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
        out[cur] = (out[cur] || 0) + Number(a.balance || 0);
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
</script>

<template>
    <div
        v-if="rows.length || uncategorizedTotals?.length"
        class="px-4 py-2 mt-2 rounded-lg border border-body-1/15 bg-base-lvl-2"
    >
        <div v-if="!hasGap" class="flex items-center gap-2 text-xs font-medium text-success">
            <i class="fa fa-check-circle" aria-hidden="true" />
            {{ $t('Your budget matches your accounts') }}
        </div>
        <div v-else class="space-y-1">
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
        <div v-if="uncategorizedTotals?.length" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 border-t border-body-1/15 pt-2">
            <a v-for="total in uncategorizedTotals" :key="total.currency_code" :href="uncategorizedHref(total.currency_code)" class="text-xs font-medium text-primary hover:underline">{{ formatMoney(total.total, total.currency_code) }} {{ $t('Uncategorized') }} · {{ total.count }} txns →</a>
        </div>
    </div>
</template>
