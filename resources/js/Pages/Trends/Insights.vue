<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import { useTrendOptions } from "./Partials/trendOptions";
const trendOptions = useTrendOptions();
import { formatMonth } from "@/utils";
import ChartComparison from "@/Components/widgets/ChartComparison.vue";
import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import LogerChart from "@/Components/organisms/LogerChart.vue";

const props = defineProps<{ data?: any; metaData?: any }>();
const { t } = useI18n();

const num = (v: any) => Number(v ?? 0);
const abs = (v: any) => Math.abs(num(v));
const money = (n: number) => {
  const s = abs(n).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).split(".");
  return { sign: n < 0 ? "−" : "", main: s[0], cents: s[1] };
};
const shortK = (n: number) => {
  const a = abs(n);
  return a >= 1000 ? `${(a / 1000).toFixed(1)}K` : a.toFixed(0);
};
const pctChange = (curr: number, prev: number) => (prev ? ((curr - prev) / Math.abs(prev)) * 100 : 0);

const chartAxis = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: "#69727F" } },
    y: { grid: { color: "rgba(255,255,255,0.05)" }, ticks: { color: "#69727F" } },
  },
};

// ---- sources
const spending = computed<any>(() => props.data?.spendingSummary ?? {});
const spendMonths = computed<any[]>(() =>
  Object.entries(spending.value)
    .map(([month, v]: any) => ({ month, total: num(v.total), data: v.data ?? [] }))
    .sort((a, b) => a.month.localeCompare(b.month))
);
const latestSpend = computed<any>(() => spendMonths.value[spendMonths.value.length - 1] ?? null);
const prevSpend = computed<any>(() => spendMonths.value[spendMonths.value.length - 2] ?? null);

const expReport = computed<any>(() => props.data?.expensesReport ?? {});
const expReportMonths = computed<any[]>(() =>
  Object.entries(expReport.value)
    .map(([month, v]: any) => ({ month, total: num(v.total) }))
    .sort((a, b) => a.month.localeCompare(b.month))
);

const nwRaw = computed<any[]>(() => props.data?.netWorth ?? []); // latest first
const nwChrono = computed<any[]>(() =>
  [...nwRaw.value].reverse().map((r: any) => ({ date_unit: r.date_unit, assets: num(r.assets), debts: num(r.debts) }))
);
const nwLatest = computed<any>(() => nwRaw.value[0] ?? null);
const nw3ago = computed<any>(() => nwRaw.value[3] ?? null);
// Only show a "vs 3 months ago" comparison when there is a genuine prior
// data point. A days-old account has no real history, so we must NOT fall
// back to the current value as the baseline (which fabricates "== today").
const showNwComparison = computed<boolean>(() => !!nw3ago.value && nw3ago.value !== nwLatest.value);

const ie = computed<any>(() => props.data?.incomeExpenses ?? {});
const expenseByCat = computed<any[]>(() =>
  Object.values(ie.value.expenses ?? {})
    .map((e: any) => ({ name: e.name, total: abs(e.total) }))
    .sort((a, b) => b.total - a.total)
);
const monthExpenseTotal = computed<number>(() => expenseByCat.value.reduce((a, x) => a + x.total, 0));
const incomeRows = computed<any[]>(() =>
  Object.values(ie.value.incomes ?? {})
    .map((i: any) => ({ name: i.name, total: abs(i.total) }))
    .sort((a, b) => b.total - a.total)
);

// money in / out breakdown lenses (Category / Payee / Member)
const rankRows = (arr: any[]) => {
  const max = Math.max(1, ...arr.map((i) => i.total));
  const tot = arr.reduce((a, i) => a + i.total, 0) || 1;
  return arr.slice(0, 8).map((i) => ({ name: i.name, amount: i.total, pct: (i.total / tot) * 100, w: (i.total / max) * 100 }));
};
const payeesInRows = computed<any[]>(() =>
  (props.data?.payeesIn ?? []).map((x: any) => ({ name: x.name, total: abs(x.total) })).sort((a, b) => b.total - a.total)
);
const payeesOutRows = computed<any[]>(() =>
  (props.data?.payeesOut ?? []).map((x: any) => ({ name: x.name, total: abs(x.total) })).sort((a, b) => b.total - a.total)
);
const breakdownDims = [
  { id: "categoria", label: "Category" },
  { id: "payee", label: "Payee" },
  { id: "miembro", label: "Member" },
];
const inDim = ref("categoria");
const outDim = ref("categoria");
const moneyInSrc = computed<any[]>(() => (inDim.value === "categoria" ? incomeRows.value : inDim.value === "payee" ? payeesInRows.value : []));
const moneyOutSrc = computed<any[]>(() => (outDim.value === "categoria" ? expenseByCat.value : outDim.value === "payee" ? payeesOutRows.value : []));
const moneyInRows = computed(() => rankRows(moneyInSrc.value));
const moneyOutRows = computed(() => rankRows(moneyOutSrc.value));
const totalIn = computed(() => moneyInSrc.value.reduce((a, i) => a + i.total, 0));
const totalOut = computed(() => moneyOutSrc.value.reduce((a, i) => a + i.total, 0));

// period-wide totals for the net cashflow summary
const grandIn = computed(() => incomeRows.value.reduce((a, i) => a + i.total, 0));
const grandOut = computed(() => expenseByCat.value.reduce((a, i) => a + i.total, 0));
const netFlow = computed(() => grandIn.value - grandOut.value);

// monthly income (Income tab main chart)
const flow = computed<any[]>(() => props.data?.monthlyFlow ?? []);
const incLabels = computed(() => flow.value.map((m: any) => formatMonth(m.month)));
const incSeries = computed(() => [{ name: t("Income"), data: flow.value.map((m: any) => abs(m.income)) }]);
const incOptions = { colors: ["#56C08AB3"], borderColors: ["#56C08A"], ...chartAxis };

// credit cards (Cards tab)
const cards = computed<any>(() => props.data?.creditCards ?? {});
const hasCards = computed(() => cards.value.hasCreditCards === true);
const cardBalances = computed<any[]>(() => (cards.value.lastCycleBalances ?? []).map((c: any) => ({ name: c.name, total: abs(c.total) })));
const cardLabels = computed(() => cardBalances.value.map((c) => c.name));
const cardSeries = computed(() => [{ name: t("Balance"), data: cardBalances.value.map((c) => c.total) }]);
const cardOptions = { colors: ["#E8837EB3"], borderColors: ["#E8837E"], ...chartAxis };

// categories bar (widget under Spending)
const catTop = computed(() => expenseByCat.value.slice(0, 12));
const catLabels = computed(() => catTop.value.map((c) => c.name));
const catSeries = computed(() => [{ name: t("Spend"), data: catTop.value.map((c) => c.total) }]);
const catOptions = { colors: ["#7C6FF0B3"], borderColors: ["#7C6FF0"], ...chartAxis };

// ---- tabs
const tabs = [
  { id: "patrimonio", label: "Net worth" },
  { id: "gastos", label: "Spending" },
  { id: "income", label: "Income" },
  { id: "cards", label: "Cards" },
];
const activeTab = ref("patrimonio");
const rangeMap: Record<string, number> = { "3M": 3, "6M": 6, "1Y": 12 };
const monthsToRange = (m: number) => (m === 12 ? "1Y" : m === 3 ? "3M" : "6M");
const range = ref(monthsToRange(Number(props.metaData?.months ?? 6)));
const setRange = (r: string) => {
  if (range.value === r) return;
  range.value = r;
  router.get(location.pathname, { months: rangeMap[r] }, { preserveState: true, preserveScroll: true, only: ["data", "metaData"] });
};

const toneClass = (tone: string) => (tone === "success" ? "text-success" : tone === "error" ? "text-error" : tone === "amber" ? "text-amber-500" : "text-body");
const usageTone = (u: number) => (u >= 70 ? "error" : u >= 30 ? "amber" : "success");

// credit-card secondary widgets
const cardTable = computed<any[]>(() =>
  (cards.value.lastCycleBalances ?? []).map((c: any) => {
    const bal = abs(c.total);
    const lim = num(c.credit_limit);
    return { name: c.name, balance: bal, limit: lim, pct: lim > 0 ? Math.min(100, (bal / lim) * 100) : 0 };
  })
);
const cardCategories = computed(() => {
  const m: Record<string, number> = {};
  (cards.value.topCategoriesByCard ?? []).forEach((x: any) => {
    m[x.cat_name] = (m[x.cat_name] || 0) + abs(x.total);
  });
  return rankRows(Object.entries(m).map(([name, total]) => ({ name, total })).sort((a, b) => b.total - a.total));
});

// ---- per-tab summary stats (top row)
const summaryStats = computed<any[]>(() => {
  const tipNet = t("Net cashflow is money in minus money out for the selected period.");
  const tipAvg = t("Average per month over the period.");
  if (activeTab.value === "gastos") {
    const avg = spendMonths.value.length ? spendMonths.value.reduce((a, m) => a + m.total, 0) / spendMonths.value.length : 0;
    return [
      { label: t("Money out"), tip: t("Total money spent in the selected period."), tone: "error", kind: "money", value: -grandOut.value },
      { label: t("Monthly average"), tip: tipAvg, tone: "body", kind: "money", value: avg },
      { label: t("Net cashflow"), tip: tipNet, tone: netFlow.value >= 0 ? "body" : "error", kind: "money", value: netFlow.value },
    ];
  }
  if (activeTab.value === "income") {
    const avg = flow.value.length ? flow.value.reduce((a, m) => a + num(m.income), 0) / flow.value.length : 0;
    return [
      { label: t("Money in"), tip: t("Total money received in the selected period."), tone: "success", kind: "money", value: grandIn.value },
      { label: t("Monthly average"), tip: tipAvg, tone: "body", kind: "money", value: avg },
      { label: t("Net cashflow"), tip: tipNet, tone: netFlow.value >= 0 ? "body" : "error", kind: "money", value: netFlow.value },
    ];
  }
  if (activeTab.value === "cards") {
    const usage = num(cards.value.creditLineUsage);
    return [
      { label: t("Cards"), tone: "body", kind: "text", value: String(cardBalances.value.length) },
      { label: t("Total Capacity"), tip: t("Combined credit limit across your cards."), tone: "body", kind: "money", value: num(cards.value.creditCapacity) },
      { label: t("Credit used"), tip: t("Total balance owed across your cards."), tone: "error", kind: "money", value: -num(cards.value.creditTotal) },
      { label: t("Credit Line Usage"), tip: t("Share of your total limit currently used."), tone: usageTone(usage), kind: "text", value: `${usage.toFixed(0)}%` },
    ];
  }
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  return [
    { label: t("Net worth"), tone: net >= 0 ? "body" : "error", kind: "money", value: net },
    { label: t("Assets"), tip: t("What you own across cash, bank and savings."), tone: "success", kind: "money", value: a },
    { label: t("Debts"), tip: t("What you owe across credit cards and loans."), tone: "error", kind: "money", value: -Math.abs(de) },
  ];
});

// ---- hero per tab
const hero = computed(() => {
  if (activeTab.value === "gastos") {
    const c = latestSpend.value?.total ?? 0;
    const p = prevSpend.value?.total ?? 0;
    const d = pctChange(c, p);
    return { label: t("Spent this month"), value: c, negative: false, sub: p ? `${d < 0 ? "−" : "+"}${Math.abs(d).toFixed(1)}% vs ${formatMonth(prevSpend.value.month)}` : "" };
  }
  if (activeTab.value === "income") {
    const li = flow.value[flow.value.length - 1]?.income ?? 0;
    const pi = flow.value[flow.value.length - 2]?.income ?? 0;
    const d = pctChange(num(li), num(pi));
    return { label: t("Income this period"), value: grandIn.value, negative: false, sub: pi ? `${d < 0 ? "−" : "+"}${Math.abs(d).toFixed(1)}% vs ${formatMonth(flow.value[flow.value.length - 2].month)}` : "" };
  }
  if (activeTab.value === "cards") {
    return {
      label: t("Credit used"),
      value: num(cards.value.creditTotal),
      negative: false,
      sub: hasCards.value ? t("{pct}% of your DOP {capacity} limit", { pct: num(cards.value.creditLineUsage).toFixed(0), capacity: money(num(cards.value.creditCapacity)).main }) : t("No credit cards yet."),
    };
  }
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  const net3 = num(nw3ago.value?.assets) + num(nw3ago.value?.debts);
  const diff = net - net3;
  return { label: t("Net worth"), value: net, negative: net < 0, sub: showNwComparison.value ? t("{sign}DOP {amount} vs 3 months ago", { sign: diff >= 0 ? "+" : "−", amount: shortK(diff) }) : "" };
});

// ---- narrative per tab
const narrative = computed<any[]>(() => {
  if (activeTab.value === "gastos") {
    const c = latestSpend.value?.total ?? 0;
    const p = prevSpend.value?.total ?? 0;
    const avg = spendMonths.value.length ? spendMonths.value.reduce((s, m) => s + m.total, 0) / spendMonths.value.length : 0;
    const out: any[] = [];
    if (p)
      out.push({ icon: c < p ? "↘" : "↗", title: c < p ? t("Spending is trending down") : t("Spending is going up"), text: t("You closed {month} at DOP {amount}, {pct}% {dir} than {prev}.", { month: formatMonth(latestSpend.value.month), amount: money(c).main, pct: Math.abs(pctChange(c, p)).toFixed(0), dir: c < p ? t("less") : t("more"), prev: formatMonth(prevSpend.value.month) }) });
    out.push({ icon: "✱", title: t("Average for the period"), text: t("You average DOP {amount} per month over the last {n} months.", { amount: money(avg).main, n: spendMonths.value.length }) });
    return out;
  }
  if (activeTab.value === "income") {
    const top = incomeRows.value[0];
    const tot = grandIn.value || 1;
    const out: any[] = [];
    if (top) out.push({ icon: "↗", title: t("Top income source"), text: t("{name} brought in DOP {amount} — {pct}% of your income.", { name: top.name, amount: money(top.total).main, pct: ((top.total / tot) * 100).toFixed(0) }) });
    out.push({ icon: "✱", title: t("Income vs spending"), text: t("You brought in DOP {in} and spent DOP {out} this period.", { in: money(grandIn.value).main, out: money(grandOut.value).main }) });
    return out;
  }
  if (activeTab.value === "cards") {
    if (!hasCards.value) return [{ icon: "✱", title: t("No credit cards yet"), text: t("Add a credit card account to see utilization and balances.") }];
    return [
      { icon: "↗", title: t("Credit utilization"), text: t("You are using {pct}% of your DOP {capacity} total limit.", { pct: num(cards.value.creditLineUsage).toFixed(0), capacity: money(num(cards.value.creditCapacity)).main }) },
      { icon: "✱", title: t("Across {n} cards", { n: cardBalances.value.length }), text: t("Total balance owed is DOP {total}.", { total: money(num(cards.value.creditTotal)).main }) },
    ];
  }
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  const net3 = num(nw3ago.value?.assets) + num(nw3ago.value?.debts);
  const out: any[] = [
    { icon: net < 0 ? "↗" : "↘", title: net < 0 ? t("Net worth is negative") : t("Net worth is positive"), text: t("Debts (DOP {debts}) {rel} assets (DOP {assets}). The net stands at {net}.", { debts: money(de).main, rel: abs(de) > a ? t("exceed") : t("are below"), assets: money(a).main, net: `${net < 0 ? "−" : ""}DOP ${money(net).main}` }) },
  ];
  if (showNwComparison.value) {
    out.push({ icon: "↗", title: t("vs 3 months ago"), text: t("Three months ago the net was {net}.", { net: `${net3 < 0 ? "−" : ""}DOP ${money(net3).main}` }) });
  }
  return out;
});

// ---- chart context label
const chartMeta = computed(() => {
  if (activeTab.value === "gastos") return { legend: t("Monthly spend"), right: t("{n} months · DOP", { n: spendMonths.value.length }) };
  if (activeTab.value === "income") return { legend: t("Monthly income"), right: t("{n} months · DOP", { n: flow.value.length }) };
  if (activeTab.value === "cards") return { legend: t("Card balances"), right: t("{n} cards", { n: cardBalances.value.length }) };
  return { legend: t("Debts vs Assets"), right: t("{n} months · DOP", { n: nwChrono.value.length }) };
});
</script>

<template>
  <AppLayout title="Insights">
    <template #header>
      <TrendSectionNav :sections="trendOptions" />
    </template>

    <div class="px-4 pb-20 mx-auto pt-16 max-w-6xl">
      <!-- summary: net cashflow = money in − money out, on the filters line -->
      <div class="flex flex-wrap items-start justify-between gap-6 mb-8 pb-6 border-b border-base-lvl-2">
        <div class="flex flex-wrap gap-8 sm:gap-14">
          <div v-for="(st, i) in summaryStats" :key="i">
            <div class="text-xs text-body-1/50 mb-1 w-max" :class="st.tip ? 'cursor-help border-b border-dotted border-body-1/30' : ''" :title="st.tip">{{ st.label }}</div>
            <div class="text-3xl font-extrabold tabular-nums leading-none" :class="toneClass(st.tone)">
              <template v-if="st.kind === 'money'"><span>{{ money(st.value).sign }}DOP {{ money(st.value).main }}</span><span class="text-base opacity-40">.{{ money(st.value).cents }}</span></template>
              <template v-else>{{ st.value }}</template>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <div class="flex rounded-lg border border-base bg-base-lvl-1 p-0.5">
            <button v-for="r in ['3M','6M','1Y']" :key="r" class="px-2.5 py-1 text-xs font-medium rounded-md transition" :class="range === r ? 'bg-base-lvl-3 text-body' : 'text-body-1/50'" @click="setRange(r)">{{ r }}</button>
          </div>
        </div>
      </div>

      <!-- body: left rail + chart -->
      <div class="grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-8">
        <div>
          <div class="flex flex-wrap gap-1 mb-6 p-1 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button v-for="tab in tabs" :key="tab.id" class="px-3 py-1.5 text-sm font-medium rounded-md transition" :class="activeTab === tab.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/50 hover:text-body-1'" @click="activeTab = tab.id">{{ $t(tab.label) }}</button>
          </div>

          <div class="space-y-5">
            <div v-for="(b, i) in narrative" :key="i" class="flex gap-3">
              <div class="text-primary text-sm leading-6 w-4 shrink-0">{{ b.icon }}</div>
              <div>
                <div class="text-sm font-bold text-body mb-0.5">{{ b.title }}</div>
                <p class="text-sm text-body-1/70 leading-relaxed">{{ b.text }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- main chart -->
        <div class="min-w-0">
          <div class="flex items-baseline justify-between mb-3">
            <span class="flex items-center gap-2 text-xs text-body-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#7C6FF0"></span>{{ chartMeta.legend }}</span>
            <span class="text-[11px] text-body-1/40">{{ chartMeta.right }}</span>
          </div>

          <div class="overflow-hidden">
            <ChartComparison v-if="activeTab === 'gastos'" key="c-gastos" class="w-full" title="" :data="spending" data-item-total="total_amount" hide-divider />
            <div v-else-if="activeTab === 'income'" key="c-inc" style="height:360px" class="p-3">
              <LogerChart v-if="incSeries[0].data.length" type="bar" :labels="incLabels" :series="incSeries" :options="incOptions" />
              <div v-else class="h-full flex items-center justify-center text-sm text-body-1/40">{{ $t('No data for this period.') }}</div>
            </div>
            <div v-else-if="activeTab === 'cards'" key="c-cards" style="height:360px" class="p-3">
              <LogerChart v-if="hasCards && cardSeries[0].data.length" type="bar" :labels="cardLabels" :series="cardSeries" :options="cardOptions" />
              <div v-else class="h-full flex items-center justify-center text-sm text-body-1/40">{{ $t('No credit cards yet.') }}</div>
            </div>
            <div v-else key="c-pat" class="p-3">
              <ChartNetWorth :data="nwChrono" type="bar" hide-headers />
            </div>
          </div>
        </div>
      </div>

      <!-- Net worth: money out + money in -->
      <div v-if="activeTab === 'patrimonio'" class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body w-max cursor-help border-b border-dotted border-body-1/20" :title="$t('Total money spent in the selected period.')">{{ $t('Money out') }}</h3>
          <div class="text-error font-bold tabular-nums mb-3">−DOP {{ money(totalOut).main }}<span class="text-xs opacity-60">.{{ money(totalOut).cents }}</span></div>
          <div class="flex gap-1 mb-4 p-0.5 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button v-for="dm in breakdownDims" :key="dm.id" class="px-3 py-1 text-xs font-medium rounded-md transition" :class="outDim === dm.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/50 hover:text-body-1'" @click="outDim = dm.id">{{ $t(dm.label) }}</button>
          </div>
          <div v-for="(r, i) in moneyOutRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:1.3fr 1.3fr auto">
            <div class="text-sm font-medium text-body truncate">{{ r.name }}</div>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#E8837E' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">−DOP {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!moneyOutRows.length" class="text-sm text-body-1/50 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body w-max cursor-help border-b border-dotted border-body-1/20" :title="$t('Total money received in the selected period.')">{{ $t('Money in') }}</h3>
          <div class="text-success font-bold tabular-nums mb-3">DOP {{ money(totalIn).main }}<span class="text-xs opacity-60">.{{ money(totalIn).cents }}</span></div>
          <div class="flex gap-1 mb-4 p-0.5 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button v-for="dm in breakdownDims" :key="dm.id" class="px-3 py-1 text-xs font-medium rounded-md transition" :class="inDim === dm.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/50 hover:text-body-1'" @click="inDim = dm.id">{{ $t(dm.label) }}</button>
          </div>
          <div v-for="(r, i) in moneyInRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:1.3fr 1.3fr auto">
            <div class="text-sm font-medium text-body truncate">{{ r.name }}</div>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#56C08A' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">DOP {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!moneyInRows.length" class="text-sm text-body-1/50 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>

      <!-- Spending: category + trend widgets -->
      <div v-else-if="activeTab === 'gastos'" class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body">{{ $t('By category') }}</h3>
          <div class="text-[11px] text-body-1/50 mb-3">{{ chartMeta.legend }}</div>
          <div style="height:300px"><LogerChart type="bar" :labels="catLabels" :series="catSeries" :options="catOptions" /></div>
        </div>
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body">{{ $t('This vs last month') }}</h3>
          <div class="text-[11px] text-body-1/50 mb-3">{{ $t('Daily cumulative') }}</div>
          <ChartCurrentVsPrevious class="w-full" title="" :data="expReport" />
        </div>
      </div>

      <!-- Income: money in breakdown -->
      <div v-else-if="activeTab === 'income'" class="mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5 md:max-w-2xl">
          <h3 class="text-lg font-extrabold text-body w-max cursor-help border-b border-dotted border-body-1/20" :title="$t('Total money received in the selected period.')">{{ $t('Money in') }}</h3>
          <div class="text-success font-bold tabular-nums mb-3">DOP {{ money(totalIn).main }}<span class="text-xs opacity-60">.{{ money(totalIn).cents }}</span></div>
          <div class="flex gap-1 mb-4 p-0.5 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button v-for="dm in breakdownDims" :key="dm.id" class="px-3 py-1 text-xs font-medium rounded-md transition" :class="inDim === dm.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/50 hover:text-body-1'" @click="inDim = dm.id">{{ $t(dm.label) }}</button>
          </div>
          <div v-for="(r, i) in moneyInRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:1.3fr 1.3fr auto">
            <div class="text-sm font-medium text-body truncate">{{ r.name }}</div>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#56C08A' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">DOP {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!moneyInRows.length" class="text-sm text-body-1/50 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>

      <!-- Cards: per-card table + expenses by category -->
      <div v-else-if="activeTab === 'cards' && hasCards" class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body mb-3">{{ $t('Credit card expenses') }}</h3>
          <div v-for="(c, i) in cardTable" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:1.4fr auto 1fr auto">
            <div class="text-sm font-medium text-body truncate">{{ c.name }}</div>
            <div class="text-right text-sm tabular-nums">DOP {{ money(c.balance).main }}</div>
            <div class="h-1 rounded-full bg-base-lvl-2 relative overflow-hidden mx-2"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: c.pct + '%', background: c.pct >= 70 ? '#E8837E' : c.pct >= 30 ? '#E7B45A' : '#56C08A' }"></span></div>
            <div class="text-right text-xs text-body-1/60 tabular-nums" style="min-width:42px">{{ c.pct.toFixed(1) }}%</div>
          </div>
          <p v-if="!cardTable.length" class="text-sm text-body-1/50 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body mb-3">{{ $t('Card expenses by category') }}</h3>
          <div v-for="(r, i) in cardCategories" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:1.3fr 1.3fr auto">
            <div class="text-sm font-medium text-body truncate">{{ r.name }}</div>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#E8837E' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">−DOP {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!cardCategories.length" class="text-sm text-body-1/50 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
