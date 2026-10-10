<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import { useTrendOptions } from "./Partials/trendOptions";
const trendOptions = useTrendOptions();
import { formatMonth } from "@/utils";
import ChartCurrentVsPrevious from "@/Components/widgets/ChartCurrentVsPrevious.vue";
import ChartNetWorth from "@/Components/ChartNetworth.vue";
import LogerChart from "@/Components/organisms/LogerChart.vue";

const props = defineProps<{ data?: any; metaData?: any }>();
const { t, locale } = useI18n();

// Currency code from the team/user settings. Falls back to DOP so
// existing behavior for the primary market (Dominican Republic) is
// preserved; USD/EUR/MXN users now see their own currency instead of
// the hardcoded "DOP" that peppered this file.
const currency = computed(
    () => (window as any)?.logerAppSettings?.currency_code ?? "DOP"
);

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
const latestSpend = computed<any>(() => spendMonths.value[spendMonths.value.length - 2] ?? null);
const prevSpend = computed<any>(() => spendMonths.value[spendMonths.value.length - 3] ?? null);

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
    .map((e: any) => ({ id: e.id, name: e.name, total: abs(e.total) }))
    .sort((a, b) => b.total - a.total)
);
const monthExpenseTotal = computed<number>(() => expenseByCat.value.reduce((a, x) => a + x.total, 0));
const incomeRows = computed<any[]>(() =>
  Object.values(ie.value.incomes ?? {})
    .map((i: any) => ({ name: i.name, total: abs(i.total) }))
    .sort((a, b) => b.total - a.total)
);

// money in / out breakdown lenses (Category / Payee / Member)
const rankRows = (arr: any[], showAll = false) => {
  const max = Math.max(1, ...arr.map((i) => i.total));
  const tot = arr.reduce((a, i) => a + i.total, 0) || 1;
  const visible = showAll ? arr : arr.slice(0, 8);
  const rows = visible.map((i) => ({ id: i.id, uncategorized: i.uncategorized, name: i.name, amount: i.total, pct: (i.total / tot) * 100, w: (i.total / max) * 100 }));
  if (!showAll && arr.length > 8) {
    const remainder = arr.slice(8).reduce((sum, row) => sum + row.total, 0);
    rows.push({ name: t("Others"), amount: remainder, pct: remainder / tot * 100, w: Math.min(100, remainder / max * 100) });
  }
  return rows;
};
const payeesInRows = computed<any[]>(() =>
  (props.data?.payeesIn ?? []).map((x: any) => ({ id: x.id, name: x.name, total: abs(x.total) })).sort((a, b) => b.total - a.total)
);
const payeesOutRows = computed<any[]>(() =>
  (props.data?.payeesOut ?? []).map((x: any) => ({ id: x.id, name: x.name, total: abs(x.total) })).sort((a, b) => b.total - a.total)
);
const breakdownDims = [
  { id: "categoria", label: "Category" },
  { id: "payee", label: "Payee" },
];
const inDim = ref("categoria");
const outDim = ref("categoria");
const moneyInSrc = computed<any[]>(() => (inDim.value === "categoria" ? incomeRows.value : inDim.value === "payee" ? payeesInRows.value : []));
const moneyOutSrc = computed<any[]>(() => (outDim.value === "categoria" ? expenseByCat.value : outDim.value === "payee" ? payeesOutRows.value : []));
const showAllIn = ref(false);
const showAllOut = ref(false);
const moneyInRows = computed(() => rankRows(moneyInSrc.value, showAllIn.value));
const moneyOutRows = computed(() => rankRows(moneyOutSrc.value, showAllOut.value));
// drill-down: a payee breakdown row links to the transactions list filtered by
// that payee, carrying the current period so the list matches what's on screen.
const periodDateParam = computed(() => {
  const s = props.metaData?.startDate, e = props.metaData?.endDate;
  return s && e ? `&filter[date]=${s}~${e}` : "";
});
const payeeHref = (id: number) => `/finance/transactions?filter[payee_id]=${id}${periodDateParam.value}`;
const totalIn = computed(() => grandIn.value);
const totalOut = computed(() => grandOut.value);

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
// Spending breakdown lens for the Gastos tab: by category (budget-aligned) or
// by account (every verified outflow, to reconcile against the bank).
const accountsOutRows = computed<any[]>(() =>
  (props.data?.expensesByAccount ?? []).map((x: any) => ({ id: x.id, name: x.name, total: abs(x.total) })).sort((a, b) => b.total - a.total)
);
const gastoBreakdownDims = [ { id: "categoria", label: "By category" }, { id: "cuenta", label: "By account" } ];
const gastoDim = ref("categoria");
const showAllGasto = ref(false);
const gastoSrc = computed<any[]>(() => {
  if (gastoDim.value === "cuenta") return accountsOutRows.value;
  // Category view: append a "Sin categoría" bucket so it reflects ALL real
  // spend and reconciles with the by-account total (both exclude transfers).
  const rows = [...expenseByCat.value];
  if (uncategorizedTotal.value > 1) rows.push({ name: t("Uncategorized"), total: uncategorizedTotal.value, uncategorized: true });
  return rows;
});
const gastoRows = computed(() => rankRows(gastoSrc.value, showAllGasto.value));
const gastoTotal = computed<number>(() => gastoSrc.value.reduce((a, x) => a + x.total, 0));
// drill-down: a breakdown row links to the transactions list filtered by that
// account (cuenta) or category (categoria), carrying the current period.
const gastoHref = (r: any) => {
  if (r.uncategorized) return uncategorizedHref.value;
  if (!r.id) return null;
  const s = props.metaData?.startDate, e = props.metaData?.endDate;
  const date = s && e ? `&filter[date]=${s}~${e}` : "";
  const key = gastoDim.value === "cuenta" ? "account_id" : "category_id";
  return `/finance/transactions?filter[${key}]=${r.id}${date}`;
};
// Uncategorized real spend = all outflows (excl. transfers) minus categorized.
// A shortcut chip links to those transactions so they can be classified.
const uncategorizedTotal = computed<number>(() => {
  const accounts = accountsOutRows.value.reduce((a, x) => a + x.total, 0);
  const categorized = expenseByCat.value.reduce((a, x) => a + x.total, 0);
  return Math.max(0, accounts - categorized);
});
const uncategorizedHref = computed(() => {
  const s = props.metaData?.startDate, e = props.metaData?.endDate;
  const date = s && e ? `&filter[date]=${s}~${e}` : "";
  return `/finance/transactions?filter[uncategorized]=1${date}`;
});
const catOptions = { colors: ["#7C6FF0B3"], borderColors: ["#7C6FF0"], ...chartAxis };

// ---- tabs
const tabs = [
  { id: "patrimonio", label: "Net worth" },
  { id: "gastos", label: "Spending" },
  { id: "income", label: "Income" },
  { id: "cards", label: "Cards" },
];
const activeTab = ref("patrimonio");
const rangeMap: Record<string, number> = { "1M": 1, "3M": 3, "6M": 6, "1Y": 12 };
// Year-to-date is dynamic (Jan..current month of this year), so it is not a
// fixed bucket in rangeMap — computed on demand when the user picks it.
const ytdMonths = () => new Date().getMonth() + 1;
const monthsToRange = (m: number) => (m === 12 ? "1Y" : m === 6 ? "6M" : m === 3 ? "3M" : m === 1 ? "1M" : "6M");
const initialMonths = Number(props.metaData?.months ?? 6);
// Prefer YTD as the active label when the returned span matches Jan..current and
// isn't a fixed bucket (avoids showing "6M" after a YTD reload in e.g. August=8).
const range = ref(props.metaData?.range ?? ((initialMonths === ytdMonths() && ![1, 3, 6, 12].includes(initialMonths)) ? "YTD" : monthsToRange(initialMonths)));
const periodLabel = computed(() => {
  const options: Intl.DateTimeFormatOptions = { month: 'short', year: 'numeric' };
  const start = props.metaData?.startDate;
  const end = props.metaData?.endDate;
  if (!start || !end) return '';
  const formatter = new Intl.DateTimeFormat(locale.value, options);
  const startLabel = formatter.format(new Date(`${start}T12:00:00`));
  const endLabel = formatter.format(new Date(`${end}T12:00:00`));
  return startLabel === endLabel ? startLabel : `${startLabel} – ${endLabel}`;
});
const balanceDate = computed(() => {
  const date = props.metaData?.asOfDate ?? nwLatest.value?.date_unit ?? props.metaData?.endDate;
  return date ? new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${date}T12:00:00`)) : '';
});
const visitPeriod = (r: string, end?: string) => {
  const months = r === "YTD" ? ytdMonths() : rangeMap[r];
  router.get(location.pathname, { months, range: r, ...(end ? { end } : {}) }, { preserveState: true, preserveScroll: true, only: ["data", "metaData"] });
};
const setRange = (r: string) => {
  if (range.value === r) return;
  range.value = r;
  visitPeriod(r, props.metaData?.isCurrentPeriod === false ? props.metaData?.anchorMonth : undefined);
};
// period navigator: step the window back/forward by its own length (1M walks month
// by month, 3M by quarter, YTD/1Y by year). The server clamps at the current month.
const isCurrentPeriod = computed(() => props.metaData?.isCurrentPeriod !== false);
const shiftPeriod = (direction: -1 | 1) => {
  const anchor = props.metaData?.anchorMonth;
  if (!anchor) return;
  const step = range.value === "YTD" ? 12 : rangeMap[range.value] ?? 1;
  const [y, m] = anchor.split("-").map(Number);
  const next = new Date(y, m - 1 + direction * step, 1);
  const label = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, "0")}`;
  visitPeriod(range.value, label);
};

const toneClass = (tone: string) => (tone === "success" ? "text-success" : tone === "error" ? "text-error" : tone === "amber" ? "text-amber-500" : "text-body");
const usageTone = (u: number) => (u >= 70 ? "error" : u >= 30 ? "amber" : "success");

// credit-card secondary widgets
const cardTable = computed<any[]>(() =>
  (cards.value.lastCycleBalances ?? []).map((c: any) => {
    const bal = abs(c.total);
    const lim = num(c.credit_limit);
    return { name: c.name, balance: bal, limit: lim, pct: lim > 0 ? (bal / lim) * 100 : 0, from: c.from, until: c.until };
  })
);
const cardCategories = computed(() => {
  const m: Record<string, number> = {};
  (cards.value.topCategoriesByCard ?? []).forEach((x: any) => {
    m[x.cat_name] = (m[x.cat_name] || 0) + abs(x.total);
  });
  return rankRows(Object.entries(m).map(([name, total]) => ({ name, total })).sort((a, b) => b.total - a.total), showAllCards.value);
});
const showAllCards = ref(false);

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
      { label: t("Last cycle amount"), tip: t("Amounts from the last closed cycle; these are not current account balances."), tone: "error", kind: "money", value: -num(cards.value.creditTotal) },
      { label: t("Cycle / limit"), tip: t("Last cycle amounts as a percentage of the combined credit limit."), tone: usageTone(usage), kind: "text", value: `${usage.toFixed(0)}%` },
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
      sub: hasCards.value ? t(`{pct}% of your {currency} {capacity} limit`, { currency: currency.value, pct: num(cards.value.creditLineUsage).toFixed(0), capacity: money(num(cards.value.creditCapacity)).main }) : t("No credit cards yet."),
    };
  }
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  const net3 = num(nw3ago.value?.assets) + num(nw3ago.value?.debts);
  const diff = net - net3;
  return { label: t("Net worth"), value: net, negative: net < 0, sub: showNwComparison.value ? t(`{sign}{currency} {amount} vs 3 months ago`, { currency: currency.value, sign: diff >= 0 ? "+" : "−", amount: shortK(diff) }) : "" };
});

// ---- narrative per tab
const narrative = computed<any[]>(() => {
  if (activeTab.value === "gastos") {
    const c = latestSpend.value?.total ?? 0;
    const p = prevSpend.value?.total ?? 0;
    const avg = spendMonths.value.length ? spendMonths.value.reduce((s, m) => s + m.total, 0) / spendMonths.value.length : 0;
    const out: any[] = [];
    if (p)
      out.push({ icon: c < p ? "↘" : "↗", title: c < p ? t("Spending is trending down") : t("Spending is going up"), text: t(`You closed {month} at {currency} {amount}, {pct}% {dir} than {prev}.`, { currency: currency.value, month: formatMonth(latestSpend.value.month), amount: money(c).main, pct: Math.abs(pctChange(c, p)).toFixed(0), dir: c < p ? t("less") : t("more"), prev: formatMonth(prevSpend.value.month) }) });
    out.push({ icon: "✱", title: t("Average for the period"), text: t(`You average {currency} {amount} per month over the last {n} months.`, { currency: currency.value, amount: money(avg).main, n: spendMonths.value.length }) });
    return out;
  }
  if (activeTab.value === "income") {
    const top = incomeRows.value[0];
    const tot = grandIn.value || 1;
    const out: any[] = [];
    if (top) out.push({ icon: "↗", title: t("Top income source"), text: t(`{name} brought in {currency} {amount} — {pct}% of your income.`, { currency: currency.value, name: top.name, amount: money(top.total).main, pct: ((top.total / tot) * 100).toFixed(0) }) });
    out.push({ icon: "✱", title: t("Income vs spending"), text: t(`You brought in {currency} {in} and spent {currency} {out} this period.`, { currency: currency.value, in: money(grandIn.value).main, out: money(grandOut.value).main }) });
    return out;
  }
  if (activeTab.value === "cards") {
    if (!hasCards.value) return [{ icon: "✱", title: t("No credit cards yet"), text: t("Add a credit card account to see utilization and balances.") }];
    return [
      { icon: "↗", title: t("Last closed cycle"), text: t("Last cycle amounts represent {pct}% of the combined credit limit.", { pct: num(cards.value.creditLineUsage).toFixed(0) }) },
      { icon: "✱", title: t("Across {n} cards", { n: cardBalances.value.length }), text: t("Last cycle total is {currency} {total}.", { currency: currency.value, total: money(num(cards.value.creditTotal)).main }) },
    ];
  }
  if (props.metaData?.hasNetWorthHistory === false) return [{ icon: "✱", title: t("No data for this period."), text: t("No verified account movements yet.") }];
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  const net3 = num(nw3ago.value?.assets) + num(nw3ago.value?.debts);
  const out: any[] = [
    { icon: net < 0 ? "↗" : "↘", title: net < 0 ? t("Net worth is negative") : t("Net worth is positive"), text: t(`Debts ({currency} {debts}) {rel} assets ({currency} {assets}). The net stands at {net}.`, { currency: currency.value, debts: money(de).main, rel: abs(de) > a ? t("exceed") : t("are below"), assets: money(a).main, net: `${net < 0 ? "−" : ""}${currency.value} ${money(net).main}` }) },
  ];
  if (showNwComparison.value) {
    out.push({ icon: "↗", title: t("vs 3 months ago"), text: t("Three months ago the net was {net}.", { net: `${net3 < 0 ? "−" : ""}${currency.value} ${money(net3).main}` }) });
  }
  return out;
});

// ---- chart context label
const periodCountLabel = (n: number) => t(n === 1 ? "{n} month · {currency}" : "{n} months · {currency}", { n, currency: currency.value });
const chartMeta = computed(() => {
  if (activeTab.value === "gastos") return { legend: t("Monthly spend"), right: periodCountLabel(spendMonths.value.length) };
  if (activeTab.value === "income") return { legend: t("Monthly income"), right: periodCountLabel(flow.value.length) };
  if (activeTab.value === "cards") return { legend: t("Last cycle amounts"), right: t("{n} cards", { n: cardBalances.value.length }) };
  return { legend: t("Debts vs Assets"), right: periodCountLabel(nwChrono.value.length) };
});
</script>

<template>
  <AppLayout title="Insights">
    <template #header>
      <TrendSectionNav :sections="trendOptions" />
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-lvl-2 bg-base-lvl-1 pl-4 pr-4 lg:pr-24 py-3">
        <div class="flex items-center gap-1">
          <button class="min-h-[36px] min-w-[36px] rounded-md text-body-1 hover:text-body hover:bg-base-lvl-3 focus-visible:ring-2 focus-visible:ring-primary" :aria-label="$t('Previous period')" @click="shiftPeriod(-1)">‹</button>
          <span class="text-sm font-semibold text-body min-w-[96px] text-center">{{ periodLabel }}</span>
          <button class="min-h-[36px] min-w-[36px] rounded-md text-body-1 hover:text-body hover:bg-base-lvl-3 focus-visible:ring-2 focus-visible:ring-primary disabled:opacity-30 disabled:pointer-events-none" :disabled="isCurrentPeriod" :aria-label="$t('Next period')" @click="shiftPeriod(1)">›</button>
        </div>
        <div class="flex gap-1" :aria-label="$t('Selected period')">
          <button v-for="r in ['1M','3M','6M','YTD','1Y']" :key="r" class="min-h-[36px] px-3 text-sm rounded-md focus-visible:ring-2 focus-visible:ring-primary" :class="range === r ? 'bg-base-lvl-3 text-body font-semibold' : 'text-body-1 hover:text-body'" :aria-pressed="range === r" @click="setRange(r)">{{ r }}</button>
        </div>
      </div>

    </template>

    <div class="px-4 pb-20 mx-auto pt-32 max-w-6xl">
      <p class="pt-4 text-xs text-body-1">{{ activeTab === 'patrimonio' ? $t('Balance as of {date}', { date: balanceDate }) : activeTab === 'cards' ? $t('Cycle amounts below; category expenses use the selected period.') : $t('Accumulated during the selected period') }}</p>
      <!-- summary: net cashflow = money in − money out, on the filters line -->
      <div class="flex flex-wrap items-start justify-between gap-6 mt-5 mb-8 pb-6 border-b border-base-lvl-2">
        <div class="flex flex-wrap gap-8 sm:gap-14">
          <div v-for="(st, i) in summaryStats" :key="i">
            <div class="text-xs text-body-1/70 mb-1 w-max" :class="st.tip ? 'cursor-help border-b border-dotted border-body-1/30' : ''" :title="st.tip">{{ st.label }}</div>
            <div class="text-3xl font-extrabold tabular-nums leading-none" :class="toneClass(st.tone)">
              <template v-if="st.kind === 'money'"><span>{{ money(st.value).sign }}{{ currency }} {{ money(st.value).main }}</span><span class="text-base opacity-40">.{{ money(st.value).cents }}</span></template>
              <template v-else>{{ st.value }}</template>
            </div>
          </div>
        </div>

      </div>

      <!-- body: left rail + chart -->
      <div class="grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-8">
        <div>
          <div class="flex flex-wrap gap-1 mb-6 p-1 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button v-for="tab in tabs" :key="tab.id" class="px-3 py-2 text-sm font-medium rounded-md transition" :class="activeTab === tab.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/70 hover:text-body-1'" :aria-pressed="activeTab === tab.id" @click="activeTab = tab.id">{{ $t(tab.label) }}</button>
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
            <span class="text-[11px] text-body-1/70">{{ chartMeta.right }}</span>
          </div>

          <div class="overflow-hidden">
            <div v-if="activeTab === 'gastos'" key="c-gastos" style="height:360px" class="p-3">
              <LogerChart v-if="grandOut" type="bar" :labels="spendMonths.map(m => formatMonth(m.month))" :series="[{ name: $t('Spending'), data: spendMonths.map(m => m.total) }]" :options="catOptions" />
              <div v-else class="h-full flex items-center justify-center text-sm text-body-1/70">{{ $t('No data for this period.') }}</div>
            </div>
            <div v-else-if="activeTab === 'income'" key="c-inc" style="height:360px" class="p-3">
              <LogerChart v-if="grandIn" type="bar" :labels="incLabels" :series="incSeries" :options="incOptions" />
              <div v-else class="h-full flex items-center justify-center text-sm text-body-1/70">{{ $t('No data for this period.') }}</div>
            </div>
            <div v-else-if="activeTab === 'cards'" key="c-cards" style="height:360px" class="p-3">
              <LogerChart v-if="hasCards && cardSeries[0].data.length" type="bar" :labels="cardLabels" :series="cardSeries" :options="cardOptions" />
              <div v-else class="h-full flex items-center justify-center text-sm text-body-1/70">{{ $t('No credit cards yet.') }}</div>
            </div>
            <div v-else key="c-pat" class="p-3">
              <ChartNetWorth v-if="metaData?.hasNetWorthHistory !== false" :data="nwChrono" type="bar" hide-headers />
              <div v-else class="h-72 flex items-center justify-center text-sm text-body-1/70">{{ $t('No data for this period.') }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Net worth: money out + money in -->
      <div v-if="activeTab === 'patrimonio'" class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body w-max cursor-help border-b border-dotted border-body-1/20" :title="$t('Total money spent in the selected period.')">{{ $t('Money out') }}</h3>
          <div class="text-error font-bold tabular-nums mb-3">−{{ currency }} {{ money(totalOut).main }}<span class="text-xs opacity-60">.{{ money(totalOut).cents }}</span></div>
          <div class="flex gap-1 mb-4 p-0.5 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button v-for="dm in breakdownDims" :key="dm.id" class="px-3 py-1 text-xs font-medium rounded-md transition" :class="outDim === dm.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/70 hover:text-body-1'" @click="outDim = dm.id; showAllOut = false">{{ $t(dm.label) }}</button>
          </div>
          <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-body-1 mb-2">
            <span>{{ periodLabel }} · {{ $t('Accumulated') }}</span>
            <button v-if="moneyOutSrc.length > 8" class="min-h-[32px] px-2 text-primary font-medium" :aria-expanded="showAllOut" @click="showAllOut = !showAllOut">{{ showAllOut ? $t('Show top 8') : $t('View all') }}</button>
          </div>
          <div v-for="(r, i) in moneyOutRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:minmax(0, 2fr) minmax(48px, 0.8fr) auto">
            <component :is="r.id ? 'a' : 'div'" :href="r.id ? payeeHref(r.id) : null" class="text-sm font-medium text-body break-words" :class="r.id ? 'hover:text-primary hover:underline' : ''" :title="r.name">{{ r.name }}</component>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#E8837E' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">−{{ currency }} {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!moneyOutRows.length" class="text-sm text-body-1/70 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body w-max cursor-help border-b border-dotted border-body-1/20" :title="$t('Total money received in the selected period.')">{{ $t('Money in') }}</h3>
          <div class="text-success font-bold tabular-nums mb-3">{{ currency }} {{ money(totalIn).main }}<span class="text-xs opacity-60">.{{ money(totalIn).cents }}</span></div>
          <p class="text-xs font-medium text-body-1 mb-4">{{ $t("By income source") }}</p>
          <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-body-1 mb-2">
            <span>{{ periodLabel }} · {{ $t('Accumulated') }}</span>
            <button v-if="moneyInSrc.length > 8" class="min-h-[32px] px-2 text-primary font-medium" :aria-expanded="showAllIn" @click="showAllIn = !showAllIn">{{ showAllIn ? $t('Show top 8') : $t('View all') }}</button>
          </div>
          <div v-for="(r, i) in moneyInRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:minmax(0, 2fr) minmax(48px, 0.8fr) auto">
            <component :is="r.id ? 'a' : 'div'" :href="r.id ? payeeHref(r.id) : null" class="text-sm font-medium text-body break-words" :class="r.id ? 'hover:text-primary hover:underline' : ''" :title="r.name">{{ r.name }}</component>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#56C08A' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">{{ currency }} {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!moneyInRows.length" class="text-sm text-body-1/70 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>

      <!-- Spending: category + trend widgets -->
      <div v-else-if="activeTab === 'gastos'" class="mt-8 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-lg font-extrabold text-body">{{ $t('By category') }}</h3>
              <div class="text-[11px] text-body-1/70">{{ chartMeta.legend }}</div>
            </div>
            <div class="text-right shrink-0">
              <div class="text-error font-bold tabular-nums leading-none">{{ currency }} {{ money(totalOut).main }}<span class="text-xs opacity-60">.{{ money(totalOut).cents }}</span></div>
              <div class="text-[10px] text-body-1/60 mt-0.5">{{ $t('Total') }} · {{ periodLabel }}</div>
            </div>
          </div>
          <div style="height:300px" class="mt-3"><LogerChart type="bar" :labels="catLabels" :series="catSeries" :options="catOptions" /></div>
          <div v-if="spendMonths.length > 1" class="mt-3 pt-3 border-t border-base-lvl-2 flex flex-wrap gap-x-4 gap-y-1">
            <span v-for="(m, i) in spendMonths" :key="i" class="text-xs text-body-1">
              <span class="text-body-1/60">{{ formatMonth(m.month) }}</span>
              <span class="font-semibold tabular-nums ml-1">{{ currency }} {{ money(m.total).main }}</span>
            </span>
          </div>
        </div>
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body">{{ $t('Recent months with activity') }}</h3>
          <div class="text-[11px] text-body-1/70 mb-3">{{ expReportMonths.map(m => formatMonth(m.month)).join(' / ') }} · {{ $t('Daily cumulative') }}</div>
          <ChartCurrentVsPrevious class="w-full" title="" :data="expReport" />
        </div>
        </div>
        <!-- Numeric breakdown: by category (budget) or by account (bank reconciliation) -->
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <h3 class="text-lg font-extrabold text-body">{{ $t('Breakdown') }}</h3>
              <div class="flex gap-1 mt-2 p-0.5 rounded-lg bg-base-lvl-1 border border-base w-max">
                <button v-for="dm in gastoBreakdownDims" :key="dm.id" class="px-3 py-1 text-xs font-medium rounded-md transition" :class="gastoDim === dm.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/70 hover:text-body-1'" @click="gastoDim = dm.id; showAllGasto = false">{{ $t(dm.label) }}</button>
              </div>
            </div>
            <div class="text-right shrink-0">
              <div class="text-error font-bold tabular-nums leading-none">{{ currency }} {{ money(gastoTotal).main }}<span class="text-xs opacity-60">.{{ money(gastoTotal).cents }}</span></div>
              <div class="text-[10px] text-body-1/60 mt-0.5">{{ $t('Total') }} · {{ $t('Excl. transfers') }} · {{ periodLabel }}</div>
              <a v-if="uncategorizedTotal > 1 && gastoDim === 'cuenta'" :href="uncategorizedHref" class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-error/15 text-error hover:bg-error/25" :title="$t('Jump to transactions without a category')">{{ $t('Uncategorized') }} · {{ currency }} {{ money(uncategorizedTotal).main }} →</a>
            </div>
          </div>
          <div class="flex items-center justify-end">
            <button v-if="gastoSrc.length > 8" class="min-h-[28px] px-2 text-primary font-medium text-xs" @click="showAllGasto = !showAllGasto">{{ showAllGasto ? $t('Show top 8') : $t('View all') }}</button>
          </div>
          <div v-for="(r, i) in gastoRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:minmax(0, 2fr) minmax(48px, 0.8fr) auto">
            <component :is="(r.id || r.uncategorized) ? 'a' : 'div'" :href="(r.id || r.uncategorized) ? gastoHref(r) : null" class="text-sm font-medium text-body break-words" :class="[(r.id || r.uncategorized) ? 'hover:text-primary hover:underline' : '', r.uncategorized ? 'text-error font-semibold' : '']" :title="r.name">{{ r.name }}</component>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#E8837E' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">{{ currency }} {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!gastoRows.length" class="text-sm text-body-1/70 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>

      <!-- Income: money in breakdown -->
      <div v-else-if="activeTab === 'income'" class="mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5 md:max-w-2xl">
          <h3 class="text-lg font-extrabold text-body w-max cursor-help border-b border-dotted border-body-1/20" :title="$t('Total money received in the selected period.')">{{ $t('Money in') }}</h3>
          <div class="text-success font-bold tabular-nums mb-3">{{ currency }} {{ money(totalIn).main }}<span class="text-xs opacity-60">.{{ money(totalIn).cents }}</span></div>
          <p class="text-xs font-medium text-body-1 mb-4">{{ $t("By income source") }}</p>
          <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-body-1 mb-2">
            <span>{{ periodLabel }} · {{ $t('Accumulated') }}</span>
            <button v-if="moneyInSrc.length > 8" class="min-h-[32px] px-2 text-primary font-medium" :aria-expanded="showAllIn" @click="showAllIn = !showAllIn">{{ showAllIn ? $t('Show top 8') : $t('View all') }}</button>
          </div>
          <div v-for="(r, i) in moneyInRows" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:minmax(0, 2fr) minmax(48px, 0.8fr) auto">
            <component :is="r.id ? 'a' : 'div'" :href="r.id ? payeeHref(r.id) : null" class="text-sm font-medium text-body break-words" :class="r.id ? 'hover:text-primary hover:underline' : ''" :title="r.name">{{ r.name }}</component>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#56C08A' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">{{ currency }} {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!moneyInRows.length" class="text-sm text-body-1/70 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>

      <!-- Cards: per-card table + expenses by category -->
      <div v-else-if="activeTab === 'cards' && hasCards" class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-8">
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body mb-3">{{ $t('Last cycle amounts') }}</h3>
          <div v-for="(c, i) in cardTable" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:1.4fr auto 1fr auto">
            <div class="text-sm font-medium text-body break-words">
              {{ c.name }}
              <div v-if="c.from && c.until" class="text-xs text-body-1/70 font-normal">{{ c.from }} – {{ c.until }}</div>
            </div>
            <div class="text-right text-sm tabular-nums">{{ currency }} {{ money(c.balance).main }}</div>
            <div class="h-1 rounded-full bg-base-lvl-2 relative overflow-hidden mx-2"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: Math.min(100, c.pct) + '%', background: c.pct >= 70 ? '#E8837E' : c.pct >= 30 ? '#E7B45A' : '#56C08A' }"></span></div>
            <div class="text-right text-xs text-body-1/60 tabular-nums" style="min-width:42px">{{ c.pct.toFixed(1) }}%</div>
          </div>
          <p v-if="!cardTable.length" class="text-sm text-body-1/70 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
        <div class="bg-base-lvl-3/50 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body mb-3">{{ $t('Card expenses by category') }}</h3>
          <div class="flex items-center justify-between gap-2 mb-3 text-xs text-body-1">
            <span>{{ periodLabel }} · {{ $t('Accumulated') }}</span>
            <button v-if="(cards.topCategoriesByCard ?? []).length > 8" class="min-h-[32px] px-2 text-primary" @click="showAllCards = !showAllCards">{{ showAllCards ? $t('Show top 8') : $t('View all') }}</button>
          </div>
          <div v-for="(r, i) in cardCategories" :key="i" class="grid items-center gap-3 py-2 border-t border-base-lvl-2" style="grid-template-columns:minmax(0, 2fr) minmax(48px, 0.8fr) auto">
            <component :is="r.id ? 'a' : 'div'" :href="r.id ? payeeHref(r.id) : null" class="text-sm font-medium text-body break-words" :class="r.id ? 'hover:text-primary hover:underline' : ''" :title="r.name">{{ r.name }}</component>
            <div class="flex items-center gap-2 text-xs text-body-1"><span style="min-width:38px">{{ r.pct.toFixed(1) }}%</span><span class="flex-1 h-1 rounded-full bg-base-lvl-2 relative overflow-hidden"><span class="absolute inset-y-0 left-0 rounded-full" :style="{ width: r.w + '%', background: '#E8837E' }"></span></span></div>
            <div class="text-right text-sm font-semibold tabular-nums">−{{ currency }} {{ money(r.amount).main }}</div>
          </div>
          <p v-if="!cardCategories.length" class="text-sm text-body-1/70 py-6 text-center">{{ $t('No data for this period.') }}</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
