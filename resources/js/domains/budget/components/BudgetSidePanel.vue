<script setup lang="ts">
import { computed } from "vue";
// @ts-ignore
import { AtDropdownLink } from "atmosphere-ui";

import JetDropdown from "@/Components/atoms/Dropdown.vue";
import LogerButtonCircle from "@/Components/atoms/LogerButtonCircle.vue";
import formatMoney from "@/utils/formatMoney";

/**
 * BudgetSidePanel
 *
 * Right-rail companion for the Budget page. Renders a titled panel
 * inspired by Actual's "My Budget" side panel:
 *   1. Header with a hamburger menu holding month-level actions
 *      (copy from last month, export CSV, export budget) so those
 *      controls have a persistent home instead of hiding behind a ⋮
 *      icon in the top toolbar.
 *   2. A Summary card with Assigned / Outflow / Available totals so
 *      the aggregate state is visible without scrolling to the
 *      TotalBudgetRow at the bottom of the table.
 *   Ready to assign lives in the hero above the table, not here.
 *   3. A #detail slot the parent can fill with BudgetDetailForm when
 *      a category is selected.
 */

const props = defineProps<{
  value: number;
  toAssign: any;
  budgets: any;
  budgetCsvExportUrl: string;
  exportBudgetUrl: string;
}>();

const emit = defineEmits<{ (e: "copy-from-previous"): void }>();

/**
 * Aggregate totals across every group. Expects the groups as useBudget
 * computes them (outflow), which carry the per-group totals the raw server
 * props do not. Same math TotalBudgetRow uses so the two surfaces agree.
 */
const totals = computed(() => {
  const groups = Array.isArray(props.budgets)
    ? props.budgets
    : ((props.budgets as any)?.data ?? []);
  return groups.reduce(
    (acc: { assigned: number; activity: number; available: number }, group: any) => {
      acc.assigned += Number(group.budgeted ?? 0);
      acc.activity += Number(group.activity ?? 0);
      acc.available += Number(group.available ?? 0);
      return acc;
    },
    { assigned: 0, activity: 0, available: 0 }
  );
});
</script>

<template>
  <div class="space-y-3">
    <!-- Panel header: title + hamburger menu -->
    <header class="flex items-center justify-between pb-1">
      <h3 class="text-lg font-semibold text-body">{{ $t('My Budget') }}</h3>
      <JetDropdown align="right" width="48">
        <template #trigger>
          <LogerButtonCircle :title="$t('Budget options')">
            <IMdiMenu />
          </LogerButtonCircle>
        </template>

        <template #content>
          <div class="w-56 py-1">
            <AtDropdownLink as="button" @click="emit('copy-from-previous')">
              <section class="flex items-center w-full">
                <IMdiContentCopy class="mr-2" />
                <span>{{ $t('Use last month\'s plan') }}</span>
              </section>
            </AtDropdownLink>

            <AtDropdownLink href="/budget-funds" as="a">
              <section class="flex items-center w-full">
                <IMdiShieldOutline class="mr-2" />
                <span>{{ $t('Emergency fund') }}</span>
              </section>
            </AtDropdownLink>

            <AtDropdownLink :href="budgetCsvExportUrl" target="_blank" as="a">
              <section class="flex items-center w-full">
                <IMdiDownload class="mr-2" />
                <span>{{ $t('Export') }} CSV</span>
              </section>
            </AtDropdownLink>

            <AtDropdownLink :href="exportBudgetUrl" target="_blank" as="a">
              <section class="flex items-center w-full">
                <IMdiExport class="mr-2" />
                <span>{{ $t('Export') }} {{ $t('Budget') }}</span>
              </section>
            </AtDropdownLink>
          </div>
        </template>
      </JetDropdown>
    </header>

    <!-- Summary card — aggregate totals across all groups. -->
    <section class="rounded-lg border border-base px-4 py-3">
      <h4 class="text-xs font-semibold text-body-1 uppercase tracking-wide mb-2">
        {{ $t('Summary') }}
      </h4>
      <div class="space-y-0.5 text-xs">
        <div class="flex justify-between items-center py-1">
          <span class="text-body-1">{{ $t('Assigned') }}</span>
          <span class="font-medium text-body tabular-nums">
            {{ formatMoney(totals.assigned) }}
          </span>
        </div>
        <div class="flex justify-between items-center py-1">
          <span class="text-body-1">{{ $t('Outflow') }}</span>
          <span class="tabular-nums text-body-1">
            {{ formatMoney(totals.activity) }}
          </span>
        </div>
        <div class="flex justify-between items-center py-1">
          <span class="text-body-1">{{ $t('Available') }}</span>
          <span
            class="tabular-nums font-semibold"
            :class="totals.available < 0 ? 'text-error' : 'text-success'"
          >
            {{ formatMoney(totals.available) }}
          </span>
        </div>
      </div>
    </section>

    <!-- Slot for category detail (BudgetDetailForm) when one is
         selected. Kept as a slot so the panel doesn't need to know
         about the detail form's props or events. -->
    <slot name="detail" />
  </div>
</template>
