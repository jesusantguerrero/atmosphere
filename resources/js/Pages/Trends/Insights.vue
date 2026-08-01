<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Components/templates/AppLayout.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import { trendOptions } from "./Partials/trendOptions";
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
const nw3ago = computed<any>(() => nwRaw.value[3] ?? nwRaw.value[nwRaw.value.length - 1] ?? null);

const ie = computed<any>(() => props.data?.incomeExpenses ?? {});
const expenseByCat = computed<any[]>(() =>
  Object.values(ie.value.expenses ?? {})
    .map((e: any) => ({ name: e.name, total: abs(e.total) }))
    .sort((a, b) => b.total - a.total)
);
const monthExpenseTotal = computed<number>(() => expenseByCat.value.reduce((a, x) => a + x.total, 0));

// Money in / money out breakdowns (shown under the Patrimonio tab).
const incomeRows = computed<any[]>(() =>
  Object.values(ie.value.incomes ?? {})
    .map((i: any) => ({ name: i.name, total: abs(i.total) }))
    .sort((a, b) => b.total - a.total)
);
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

// ---- tabs
const tabs = [
  { id: "gastos", label: "Spending" },
  { id: "categorias", label: "Categories" },
  { id: "tendencia", label: "Trend" },
  { id: "patrimonio", label: "Net worth" },
];
const activeTab = ref("gastos");
const rangeMap: Record<string, number> = { "3M": 3, "6M": 6, "1Y": 12 };
const monthsToRange = (m: number) => (m === 12 ? "1Y" : m === 3 ? "3M" : "6M");
const range = ref(monthsToRange(Number(props.metaData?.months ?? 6)));
const setRange = (r: string) => {
  if (range.value === r) return;
  range.value = r;
  router.get(location.pathname, { months: rangeMap[r] }, { preserveState: true, preserveScroll: true, only: ["data", "metaData"] });
};

// ---- hero per tab
const hero = computed(() => {
  if (activeTab.value === "gastos") {
    const c = latestSpend.value?.total ?? 0;
    const p = prevSpend.value?.total ?? 0;
    const d = pctChange(c, p);
    return {
      label: t("Spent this month"),
      value: c,
      negative: false,
      sub: p ? `${d < 0 ? "−" : "+"}${Math.abs(d).toFixed(1)}% vs ${formatMonth(prevSpend.value.month)}` : "",
    };
  }
  if (activeTab.value === "categorias") {
    const top = expenseByCat.value[0];
    const tot = monthExpenseTotal.value || 1;
    return {
      label: t("Top category"),
      value: top?.total ?? 0,
      negative: false,
      sub: top ? t("{name} · {pct}% of the month", { name: top.name, pct: ((top.total / tot) * 100).toFixed(0) }) : "",
    };
  }
  if (activeTab.value === "tendencia") {
    const cur = expReportMonths.value[expReportMonths.value.length - 1]?.total ?? 0;
    const prv = expReportMonths.value[expReportMonths.value.length - 2]?.total ?? 0;
    const diff = cur - prv;
    return {
      label: t("Accrued to date"),
      value: cur,
      negative: false,
      sub: prv ? t("DOP {amount} {dir} last month", { amount: shortK(diff), dir: diff < 0 ? t("below") : t("above") }) : "",
    };
  }
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  const a3 = num(nw3ago.value?.assets);
  const d3 = num(nw3ago.value?.debts);
  const net3 = a3 + d3;
  const diff = net - net3;
  return {
    label: t("Net worth"),
    value: net,
    negative: net < 0,
    sub: nw3ago.value ? t("{sign}DOP {amount} vs 3 months ago", { sign: diff >= 0 ? "+" : "−", amount: shortK(diff) }) : "",
  };
});

// ---- narrative per tab
const narrative = computed<any[]>(() => {
  if (activeTab.value === "gastos") {
    const c = latestSpend.value?.total ?? 0;
    const p = prevSpend.value?.total ?? 0;
    const avg = spendMonths.value.length ? spendMonths.value.reduce((s, m) => s + m.total, 0) / spendMonths.value.length : 0;
    const out: any[] = [];
    if (p)
      out.push({
        icon: c < p ? "↘" : "↗",
        title: c < p ? t("Spending is trending down") : t("Spending is going up"),
        text: t("You closed {month} at DOP {amount}, {pct}% {dir} than {prev}.", { month: formatMonth(latestSpend.value.month), amount: money(c).main, pct: Math.abs(pctChange(c, p)).toFixed(0), dir: c < p ? t("less") : t("more"), prev: formatMonth(prevSpend.value.month) }),
      });
    out.push({
      icon: "✱",
      title: t("Average for the period"),
      text: t("You average DOP {amount} per month over the last {n} months.", { amount: money(avg).main, n: spendMonths.value.length }),
    });
    return out;
  }
  if (activeTab.value === "categorias") {
    const cats = expenseByCat.value;
    const tot = monthExpenseTotal.value || 1;
    const top3 = cats.slice(0, 3).reduce((s, x) => s + x.total, 0);
    const small = cats.filter((c) => c.total < 15000).length;
    const out: any[] = [];
    if (cats[0])
      out.push({
        icon: "✱",
        title: t("Spending concentration"),
        text: t("The top 3 categories explain {pct}% of the total. {name} alone adds up to DOP {amount}.", { pct: ((top3 / tot) * 100).toFixed(0), name: cats[0].name, amount: money(cats[0].total).main }),
      });
    out.push({
      icon: "↘",
      title: t("The long tail"),
      text: t("{n} categories are below DOP 15K.", { n: small }),
    });
    return out;
  }
  if (activeTab.value === "tendencia") {
    const cur = expReportMonths.value[expReportMonths.value.length - 1]?.total ?? 0;
    const prv = expReportMonths.value[expReportMonths.value.length - 2]?.total ?? 0;
    const diff = cur - prv;
    const out: any[] = [];
    out.push({
      icon: "↘",
      title: cur < prv ? t("You are below last month") : t("You are above last month"),
      text: t("You have accrued DOP {cur} against DOP {prev} last month — a DOP {diff} difference {dir}.", { cur: money(cur).main, prev: money(prv).main, diff: shortK(Math.abs(diff)), dir: diff < 0 ? t("in your favor") : t("against you") }),
    });
    return out;
  }
  const a = num(nwLatest.value?.assets);
  const de = num(nwLatest.value?.debts);
  const net = a + de;
  const a3 = num(nw3ago.value?.assets);
  const d3 = num(nw3ago.value?.debts);
  const net3 = a3 + d3;
  const out: any[] = [];
  out.push({
    icon: net < 0 ? "↗" : "↘",
    title: net < 0 ? t("Net worth is negative") : t("Net worth is positive"),
    text: t("Debts (DOP {debts}) {rel} assets (DOP {assets}). The net stands at {net}.", { debts: money(de).main, rel: abs(de) > a ? t("exceed") : t("are below"), assets: money(a).main, net: `${net < 0 ? "−" : ""}DOP ${money(net).main}` }),
  });
  out.push({
    icon: "↗",
    title: t("vs 3 months ago"),
    text: t("Three months ago the net was {net}.", { net: `${net3 < 0 ? "−" : ""}DOP ${money(net3).main}` }),
  });
  return out;
});

// ---- right-side chart context label
const chartMeta = computed(() => {
  if (activeTab.value === "gastos") return { legend: t("Monthly spend"), right: t("{n} months · DOP", { n: spendMonths.value.length }) };
  if (activeTab.value === "categorias") return { legend: t("{month} by category", { month: latestSpend.value ? formatMonth(latestSpend.value.month) : "" }), right: t("{n} categories", { n: expenseByCat.value.length }) };
  if (activeTab.value === "tendencia") return { legend: t("Daily cumulative"), right: t("This vs last month") };
  return { legend: t("Debts vs Assets"), right: t("{n} months · DOP", { n: nwChrono.value.length }) };
});

// ---- categories bar chart
const catTop = computed(() => expenseByCat.value.slice(0, 12));
const catLabels = computed(() => catTop.value.map((c) => c.name));
const catSeries = computed(() => [{ name: t("Spend"), data: catTop.value.map((c) => c.total) }]);
const catOptions = {
  colors: ["#7C6FF0"],
  borderColors: ["#7C6FF0"],
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { color: "#69727F" } },
    y: { grid: { color: "rgba(255,255,255,0.05)" }, ticks: { color: "#69727F" } },
  },
};
</script>

<template>
  <AppLayout title="Insights">
    <template #header>
      <TrendSectionNav :sections="trendOptions" />
    </template>

    <div class="px-4 pb-20 mx-auto pt-16 max-w-6xl">
      <!-- toolbar -->
      <div class="flex flex-wrap items-center gap-2 mb-6">
        <button class="px-3 py-1.5 rounded-lg text-sm border border-base bg-base-lvl-1 text-body-1/70">{{ $t('Filters') }}</button>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm border border-base bg-base-lvl-1 text-body-1/70">
          <span>{{ chartMeta.right }}</span>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <div class="flex rounded-lg border border-base bg-base-lvl-1 p-0.5">
            <button
              v-for="r in ['3M','6M','1Y']"
              :key="r"
              class="px-2.5 py-1 text-xs font-medium rounded-md transition"
              :class="range === r ? 'bg-base-lvl-3 text-body' : 'text-body-1/50'"
              @click="setRange(r)"
            >{{ r }}</button>
          </div>
        </div>
      </div>

      <!-- body: left rail + chart -->
      <div class="grid grid-cols-1 lg:grid-cols-[340px_1fr] gap-8">
        <!-- left rail -->
        <div>
          <div class="text-xs text-body-1/50 mb-1">{{ hero.label }}</div>
          <div class="text-4xl font-extrabold tabular-nums leading-none" :class="hero.negative ? 'text-error' : 'text-body'">
            <span>{{ money(hero.value).sign }}DOP {{ money(hero.value).main }}</span><span class="text-xl opacity-40">.{{ money(hero.value).cents }}</span>
          </div>
          <div class="text-xs text-body-1/50 mt-1.5">{{ hero.sub }}</div>

          <!-- tab selector -->
          <div class="flex flex-wrap gap-1 mt-5 mb-6 p-1 rounded-lg bg-base-lvl-1 border border-base w-max">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              class="px-3 py-1.5 text-sm font-medium rounded-md transition"
              :class="activeTab === tab.id ? 'bg-base-lvl-3 text-body' : 'text-body-1/50 hover:text-body-1'"
              @click="activeTab = tab.id"
            >{{ $t(tab.label) }}</button>
          </div>

          <!-- narrative -->
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

        <!-- chart panel -->
        <div class="min-w-0">
          <div class="flex items-baseline justify-between mb-3">
            <span class="flex items-center gap-2 text-xs text-body-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#7C6FF0"></span>{{ chartMeta.legend }}</span>
            <span class="text-[11px] text-body-1/40">{{ chartMeta.right }}</span>
          </div>

          <div class="bg-base-lvl-3 border border-base rounded-xl overflow-hidden">
            <ChartComparison
              v-if="activeTab === 'gastos'"
              key="c-gastos"
              class="w-full"
              title=""
              :data="spending"
              data-item-total="total_amount"
              hide-divider
            />
            <div v-else-if="activeTab === 'categorias'" key="c-cat" style="height:360px" class="p-3">
              <LogerChart type="bar" :labels="catLabels" :series="catSeries" :options="catOptions" />
            </div>
            <ChartCurrentVsPrevious
              v-else-if="activeTab === 'tendencia'"
              key="c-tend"
              class="w-full"
              title=""
              :data="expReport"
            />
            <div v-else key="c-pat" class="p-3">
              <ChartNetWorth :data="nwChrono" type="bar" hide-headers />
            </div>
          </div>
        </div>
      </div>

      <!-- money in / money out — appears under the Patrimonio tab -->
      <div v-if="activeTab === 'patrimonio'" class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-8">
        <div class="bg-base-lvl-3 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body">{{ $t('Money out') }}</h3>
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
        <div class="bg-base-lvl-3 border border-base rounded-xl p-5">
          <h3 class="text-lg font-extrabold text-body">{{ $t('Money in') }}</h3>
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
    </div>
  </AppLayout>
</template>
