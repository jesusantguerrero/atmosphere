<script setup lang="ts">
import { ref, toRefs, computed } from "vue";
// @ts-ignore
import { AtDatePager } from "atmosphere-ui";
import { NDropdown } from "naive-ui";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";

import TransactionsList from "@/domains/transactions/components/TransactionsList.vue";
import WatchlistModal from "@/domains/watchlist/components/WatchlistModal.vue";
import WatchlistSummaryCard from "@/domains/watchlist/components/WatchlistSummaryCard.vue";
import WatchlistTimelineChart from "@/domains/watchlist/components/WatchlistTimelineChart.vue";

import { useServerSearch } from "@/composables/useServerSearch";
import { formatMoney, formatDate, formatMonth, MonthTypeFormat } from "@/utils";
import { getVariances } from "@/domains/transactions";

import { IServerSearchData } from "@/composables/useServerSearchV2";
import { WatchlistResource } from "@/domains/watchlist/models";

const props = withDefaults(defineProps<{
  user: Object,
  resource: WatchlistResource,
  serverSearchOptions: IServerSearchData,
  watchlist: Record<string, string>[]
}>(), {});

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true,
    defaultDates: true,
});

const isModalOpen = ref(false);
const resourceToEdit = ref<Record<string, unknown> | null>(null);

const sectionName = computed(() => {
    const month = formatMonth(pageState?.dates.startDate, MonthTypeFormat.long);
    return `${props.resource.name} in ${month}`;
});

const target = computed(() => Number(props.resource.target ?? 0));
const monthTotal = computed(() => Number(props.resource.month?.total ?? 0));
const prevMonthTotal = computed(() => Number(props.resource.prevMonth?.total ?? 0));
const monthProjected = computed(() => Number(props.resource.month?.projected ?? 0));
const isCurrentPeriod = computed(() => Boolean(props.resource.month?.is_current_period));
const daysElapsed = computed(() => Number(props.resource.month?.days_elapsed ?? 0));
const daysInPeriod = computed(() => Number(props.resource.month?.days_in_period ?? 0));

const transactionsCount = computed(() => Number(props.resource.month?.transactionsCount ?? 0));
const lastTransactionDate = computed(() => props.resource.month?.lastTransactionDate ?? null);
const hasActivity = computed(() => transactionsCount.value > 0);

const hasTarget = computed(() => target.value > 0);
const targetRatio = computed(() => hasTarget.value ? monthTotal.value / target.value : 0);
const projectedRatio = computed(() => hasTarget.value ? monthProjected.value / target.value : 0);

const trafficLight = computed(() => {
    if (!hasTarget.value) return null;
    if (targetRatio.value > 1) return { label: 'Over target', bar: 'bg-error', chip: 'bg-error/10 text-error border-error' };
    if (targetRatio.value > 0.7) return { label: 'Close to target', bar: 'bg-warning', chip: 'bg-warning/10 text-warning border-warning' };
    return { label: 'On track', bar: 'bg-success', chip: 'bg-success/10 text-success border-success' };
});

const projectionTone = computed(() => {
    if (!hasTarget.value || !isCurrentPeriod.value) return 'text-body-1';
    if (projectedRatio.value > 1) return 'text-error font-bold';
    if (projectedRatio.value > 0.7) return 'text-warning font-bold';
    return 'text-success font-bold';
});

// Variance vs last month (positive = spending more = bad for expenses)
const variance = computed(() => {
    return getVariances(monthTotal.value, prevMonthTotal.value);
});

const varianceLabel = computed(() => {
    if (variance.value === null) return null;
    const sign = Number(variance.value) > 0 ? '+' : '';
    return `${sign}${variance.value}% vs last month`;
});

const varianceTone = computed(() => {
    if (variance.value === null || Number(variance.value) === 0) return 'text-body-1';
    return Number(variance.value) > 0 ? 'text-error' : 'text-success';
});

const transactionMeta = computed(() => {
    const parts: string[] = [];
    if (transactionsCount.value > 0) {
        parts.push(`${transactionsCount.value} ${transactionsCount.value === 1 ? 'transaction' : 'transactions'}`);
    }
    if (lastTransactionDate.value) {
        parts.push(`last on ${formatDate(lastTransactionDate.value, '--')}`);
    }
    return parts.join(' · ');
});

const headerMenuOptions = computed(() => {
    const opts: any[] = [{ key: 'edit', label: 'Edit' }];
    const hasShare = Boolean((props.resource as any).share_token);
    opts.push({
        key: 'share',
        label: hasShare ? 'Copy share link' : 'Enable public link',
    });
    if (hasShare) {
        opts.push({ key: 'unshare', label: 'Disable public link' });
    }
    opts.push({ key: 'delete', label: 'Delete', props: { class: 'text-error' } });
    return opts;
});

const shareUrl = computed(() => {
    const token = (props.resource as any).share_token;
    if (!token || typeof window === 'undefined') return null;
    return `${window.location.origin}/share/watchlist/${token}`;
});

const enableShareAndCopy = () => {
    const id = (props.resource as any).id;
    router.post(`/finance/watchlist/${id}/share`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            // After reload, share_token is set; copy on next tick.
            setTimeout(() => {
                if (shareUrl.value && navigator.clipboard) {
                    navigator.clipboard.writeText(shareUrl.value);
                }
            }, 100);
        },
    });
};

const copyExistingShareLink = () => {
    if (shareUrl.value && navigator.clipboard) {
        navigator.clipboard.writeText(shareUrl.value);
    }
};

const disableShare = () => {
    const id = (props.resource as any).id;
    router.delete(`/finance/watchlist/${id}/share`, { preserveScroll: true });
};

const openEdit = () => {
    resourceToEdit.value = {
        id: (props.resource as any).id,
        name: props.resource.name,
        type: (props.resource as any).type,
        input: (props.resource as any).input ?? [],
        target: props.resource.target,
    };
    isModalOpen.value = true;
};

const goBack = () => {
    const search = typeof window !== 'undefined' ? window.location.search : '';
    router.visit(`/finance/watchlist${search}`);
};

const handleDelete = () => {
    if (!window.confirm(`Delete watchlist "${props.resource.name}"?`)) {
        return;
    }
    router.delete(`/finance/watchlist/${(props.resource as any).id}`, {
        onSuccess: goBack,
    });
};

const onHeaderMenuSelect = (key: string) => {
    if (key === 'edit') openEdit();
    if (key === 'delete') handleDelete();
    if (key === 'share') {
        if ((props.resource as any).share_token) {
            copyExistingShareLink();
        } else {
            enableShareAndCopy();
        }
    }
    if (key === 'unshare') disableShare();
};

const parser = (transaction: Record<string, string>) => ({
    title: transaction.description,
    subtitle: `${transaction?.account_from?.name} -> ${transaction.cat_name}`,
    date: transaction.date,
    value: transaction.amount,
    currencyCode: transaction.currency_code,
    status: transaction.status,
});

const transactions = computed(() => {
    const data = Object.values(props.resource.transactions).reduce((allData: any[], val: any) => {
        allData.push(...(val?.data ?? []));
        return allData;
    }, []);
    return data.map(parser);
});

const onClick = (itemId: number) => {
    if ((props.resource as any).id == itemId) return;
    router.visit(`/finance/watchlist/${itemId}?${location.search}`);
};

const monthlySeries = computed(() => {
    const raw = (props.resource as any).monthlySeries ?? [];
    return Array.isArray(raw) ? raw : [];
});
</script>

<template>
  <AppLayout :title="sectionName" :show-back-button="true" @back="goBack">
    <template #header>
      <FinanceSectionNav />
    </template>

    <FinanceTemplate ref="financeTemplateRef">
      <article class="w-full space-y-4">
        <section class="flex flex-wrap items-center justify-end gap-2 pt-4">
          <AtDatePager
            class="h-10 border-none rounded-md bg-base-lvl-1 text-body"
            v-model:startDate="pageState.dates.startDate"
            v-model:endDate="pageState.dates.endDate"
            controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
            next-mode="month"
            @change="executeSearchWithDelay(5)"
          />
          <LogerButton variant="inverse" @click="openEdit">Edit</LogerButton>
          <NDropdown trigger="click" :options="headerMenuOptions" @select="onHeaderMenuSelect">
            <button type="button" class="text-body-1 hover:text-body p-1 rounded" aria-label="More actions">
              <i class="fa fa-ellipsis-v" />
            </button>
          </NDropdown>
        </section>

        <header class="bg-base-lvl-3 rounded-md px-5 py-4 border border-base">
          <p class="text-xs uppercase tracking-wide text-body-1 font-semibold">This month</p>
          <div class="flex items-baseline gap-3 mt-1 flex-wrap">
            <h1 class="text-3xl font-extrabold text-body leading-none">
              {{ formatMoney(monthTotal) }}
            </h1>
            <span v-if="varianceLabel" class="text-sm font-semibold" :class="varianceTone">
              {{ varianceLabel }}
            </span>
            <span v-if="prevMonthTotal > 0" class="text-xs text-body-1">
              (last month {{ formatMoney(prevMonthTotal) }})
            </span>
          </div>
          <p v-if="hasActivity" class="text-xs text-body-1 mt-2">{{ transactionMeta }}</p>
          <p v-else class="text-xs text-body-1 mt-2">No transactions this month yet.</p>
        </header>

        <section v-if="hasTarget" class="bg-base-lvl-3 rounded-md p-4 border" :class="trafficLight.chip">
          <div class="flex items-center justify-between mb-2">
            <div>
              <h4 class="font-bold text-body">Monthly target</h4>
              <p class="text-xs text-body-1">{{ trafficLight.label }} — {{ Math.round(targetRatio * 100) }}% of limit</p>
            </div>
            <div class="text-right">
              <div class="text-lg font-bold">{{ formatMoney(monthTotal) }}</div>
              <div class="text-xs text-body-1">of {{ formatMoney(target) }}</div>
            </div>
          </div>
          <div class="h-2 rounded-full bg-base-lvl-2 overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="trafficLight.bar"
              :style="{ width: Math.min(100, targetRatio * 100) + '%' }"
            />
          </div>
          <div v-if="isCurrentPeriod" class="mt-3 pt-3 border-t border-base/40 flex items-baseline justify-between text-sm">
            <div>
              <span class="text-body-1">End-of-month projection</span>
              <span class="text-xs text-body-1 ml-2">(day {{ daysElapsed }} of {{ daysInPeriod }})</span>
            </div>
            <div class="text-right">
              <span :class="projectionTone">{{ formatMoney(monthProjected) }}</span>
              <span class="text-xs text-body-1 ml-1">({{ Math.round(projectedRatio * 100) }}%)</span>
            </div>
          </div>
        </section>

        <section v-else class="bg-base-lvl-3 rounded-md p-4 border border-base flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h4 class="font-bold text-body">No monthly target set</h4>
            <p class="text-xs text-body-1 mt-1 max-w-md">
              Set a target to get a status indicator, end-of-month projection, and a notification when you cross your limit.
            </p>
          </div>
          <LogerButton variant="inverse" @click="openEdit">+ Set monthly target</LogerButton>
        </section>

        <WatchlistTimelineChart
          v-if="monthlySeries.length"
          :series="monthlySeries"
          :target="target"
        />

        <TransactionsList
          class="w-full"
          table-class="overflow-auto text-sm"
          :transactions="transactions"
        />
      </article>

      <WatchlistModal
        v-if="isModalOpen"
        v-model:show="isModalOpen"
        :form-data="resourceToEdit"
      />

      <template #panel>
        <section class="grid lg:grid-cols-1 gap-2 pt-4">
          <WatchlistSummaryCard
            v-for="item in watchlist"
            :key="(item as any).id"
            :startDate="pageState.dates.startDate"
            :item="item"
            class="w-full"
            @click="onClick((item as any).id)"
          />
        </section>
      </template>
    </FinanceTemplate>
  </AppLayout>
</template>
