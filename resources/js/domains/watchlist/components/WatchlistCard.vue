<script setup lang="ts">
import { computed } from "vue";
import { NDropdown, NTooltip } from "naive-ui";

import { formatMoney, formatDate } from "@/utils";
import { getVariances } from "@/domains/transactions";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits<{
  (e: 'edit', item: Record<string, any>): void;
  (e: 'delete', item: Record<string, any>): void;
}>();

const lastMonthVariance = computed(() => {
  return getVariances(props.item.data.month.total, props.item.data.prevMonth.total) || 0;
});

const typeLabels: Record<string, string> = {
  categories: 'Category',
  groups: 'Category Group',
  payees: 'Payee',
  tags: 'Tag',
};

const subtitle = computed(() => {
  const type = typeLabels[props.item.type as string] ?? 'Watchlist';
  const count = Array.isArray(props.item.input) ? props.item.input.length : 0;
  if (count === 0) return type;
  const itemLabel = count === 1 ? 'item' : 'items';
  return `${type} · ${count} ${itemLabel}`;
});

const menuOptions = [
  { key: 'edit', label: 'Edit' },
  { key: 'delete', label: 'Delete', props: { class: 'text-error' } },
];

const onMenuSelect = (key: string) => {
  if (key === 'edit') emit('edit', props.item);
  if (key === 'delete') emit('delete', props.item);
};

const target = computed(() => Number(props.item.target ?? 0));
const monthTotal = computed(() => Number(props.item.data?.month?.total ?? 0));
const monthProjected = computed(() => Number(props.item.data?.month?.projected ?? 0));
const isCurrentPeriod = computed(() => Boolean(props.item.data?.month?.is_current_period));
const hasTarget = computed(() => target.value > 0);

const targetRatio = computed(() => {
  if (!hasTarget.value) return 0;
  return monthTotal.value / target.value;
});

const projectedRatio = computed(() => {
  if (!hasTarget.value) return 0;
  return monthProjected.value / target.value;
});

const projectedExceedsTarget = computed(() => {
  return hasTarget.value && isCurrentPeriod.value && projectedRatio.value > 1 && targetRatio.value <= 1;
});

const transactionsCount = computed(() => Number(props.item.data?.month?.transactionsCount ?? 0));
const hasActivity = computed(() => transactionsCount.value > 0);

const trafficLight = computed(() => {
  if (!hasTarget.value) return null;
  if (targetRatio.value > 1) return 'red';
  if (targetRatio.value > 0.7) return 'amber';
  return 'green';
});

const trafficLightClass = computed(() => {
  switch (trafficLight.value) {
    case 'red': return 'bg-error';
    case 'amber': return 'bg-warning';
    case 'green': return 'bg-success';
    default: return 'bg-base-lvl-2';
  }
});

const trafficLightText = computed(() => {
  switch (trafficLight.value) {
    case 'red': return 'text-error';
    case 'amber': return 'text-warning';
    case 'green': return 'text-success';
    default: return 'text-body-1';
  }
});

const trafficLightLabel = computed(() => {
  switch (trafficLight.value) {
    case 'red': return 'Over target';
    case 'amber': return 'Approaching limit';
    case 'green': return 'On track';
    default: return '';
  }
});

const trafficLightTooltip = computed(() => {
  if (!hasTarget.value) return '';
  const pct = Math.round(targetRatio.value * 100);
  return `${trafficLightLabel.value} — ${pct}% of ${formatMoney(target.value)}`;
});

const streakMonths = computed(() => Number(props.item.streak_months ?? 0));
const streakLabel = computed(() => {
  const n = streakMonths.value;
  return n === 1 ? '1 month under target' : `${n} months under target`;
});
</script>

<template>
  <article class="overflow-hidden bg-white rounded-lg shadow-md">
    <header class="p-4 flex items-start justify-between">
      <div class="min-w-0">
        <h4 class="font-bold text-primary truncate">{{ item.name }}</h4>
        <p class="text-xs text-body-1 mt-0.5">{{ subtitle }}</p>
        <NTooltip v-if="streakMonths > 0" trigger="hover" placement="bottom-start">
          <template #trigger>
            <span class="mt-1.5 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-warning/15 text-warning text-xs font-semibold cursor-help">
              <span aria-hidden="true">🔥</span>
              {{ streakMonths }}
            </span>
          </template>
          {{ streakLabel }}
        </NTooltip>
      </div>
      <div class="flex items-center gap-2 shrink-0 ml-2">
        <NTooltip v-if="hasTarget" trigger="hover" placement="top">
          <template #trigger>
            <span
              class="w-2.5 h-2.5 rounded-full cursor-help"
              :class="trafficLightClass"
            />
          </template>
          {{ trafficLightTooltip }}
        </NTooltip>
        <NDropdown
          trigger="click"
          :options="menuOptions"
          @select="onMenuSelect"
        >
          <button
            type="button"
            class="text-body-1 hover:text-body p-1 rounded"
            @click.stop
            aria-label="More actions"
          >
            <i class="fa fa-ellipsis-v" />
          </button>
        </NDropdown>
      </div>
    </header>
    <section class="px-4 pb-4">
      <div
        v-if="projectedExceedsTarget"
        class="mb-3 px-3 py-2 rounded-md bg-warning/10 text-warning text-xs font-semibold flex items-center gap-2"
      >
        <i class="fa fa-triangle-exclamation" />
        <span>Projected to exceed target this month</span>
      </div>

      <div class="px-4 py-2 rounded-md bg-secondary/10">
        <h5 class="text-body-1/80">This Month</h5>
        <h4 v-if="hasActivity" class="font-bold" :class="hasTarget ? trafficLightText : 'text-secondary'">
          {{ formatMoney(monthTotal) }}
        </h4>
        <h4 v-else class="font-medium text-body-1 text-base">
          No spending yet
        </h4>
      </div>

      <div v-if="hasTarget" class="mt-3">
        <div class="flex justify-between text-xs text-body-1 mb-1">
          <span>{{ Math.round(targetRatio * 100) }}% of target</span>
          <span>{{ formatMoney(target) }}</span>
        </div>
        <div class="h-2 rounded-full bg-base-lvl-2 overflow-hidden">
          <div
            class="h-full rounded-full transition-all"
            :class="trafficLightClass"
            :style="{ width: Math.min(100, targetRatio * 100) + '%' }"
          />
        </div>
        <div v-if="isCurrentPeriod && hasActivity" class="mt-1.5 text-xs flex justify-between" :class="projectedExceedsTarget ? 'text-warning' : 'text-body-1'">
          <span>End-of-month projection:</span>
          <span class="font-semibold">{{ formatMoney(monthProjected) }} ({{ Math.round(projectedRatio * 100) }}%)</span>
        </div>
      </div>

      <div class="flex justify-between space-x-2 text-xs">
        <div class="w-full px-5 py-2 mt-4 bg-opacity-25 rounded-md bg-secondary/10">
          <span class="text-body-1/80">Last month:</span>
          <span class="font-bold text-secondary">{{ lastMonthVariance }}%</span>
        </div>
        <button
          v-if="!hasTarget"
          type="button"
          class="w-full px-5 py-2 mt-4 rounded-md bg-primary/10 text-primary font-semibold hover:bg-primary/20 transition"
          @click.stop="emit('edit', item)"
        >
          + Set monthly target
        </button>
      </div>
    </section>
    <section
      v-if="hasActivity"
      class="grid grid-cols-2 p-4 pb-2 mx-4 text-white bg-primary rounded-xl"
    >
      <div class="text-center">
        <h4>{{ formatDate(item.data.month.lastTransactionDate, '--') }}</h4>
        <span class="text-xs">Last transaction date</span>
      </div>
      <div class="text-center">
        <h4>{{ transactionsCount }}</h4>
        <span class="text-xs">Transactions</span>
      </div>
    </section>
    <section v-else class="mx-4 mb-2 px-4 py-3 text-center bg-base-lvl-2 rounded-xl text-body-1 text-sm">
      Waiting for the first transaction this month
    </section>
    <footer class="px-4 pt-2 pb-4 text-primary">
      <button>Show more</button>
    </footer>
  </article>
</template>
