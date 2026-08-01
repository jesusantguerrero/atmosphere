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
 *   2. A contextual Status card — coral when overspent, amber when
 *      money is still unassigned, green when the plan is balanced.
 *      Uses the same tokens as BudgetBalanceAssign but formatted as
 *      an informational stat block, not an actionable pill.
 *   3. A Summary card with Assigned / Outflow / Available totals so
 *      the aggregate state is visible without scrolling to the
 *      TotalBudgetRow at the bottom of the table.
 *   4. A #detail slot the parent can fill with BudgetDetailForm when
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
 * Aggregate totals across every visible group. Same shape and math
 * TotalBudgetRow uses so the two surfaces stay in sync.
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

/**
 * Contextual status card content. Each variant maps to a distinct
 * color token so the panel section reads as an alert without
 * requiring the user to parse the amount first. Copy leans on the
 * app's existing i18n keys where possible.
 */
const status = computed(() => {
  const v = Number(props.value ?? 0);
  if (v < 0) {
    return {
      variant: "overspent",
      label: "Overspent",
      colorClass:
        "bg-primary/10 border-primary/40 text-primary",
      description:
        "You assigned more than you have. Take money from another category or add funds to balance the month.",
      amount: v,
    };
  }
  if (v > 0) {
    return {
      variant: "unassigned",
      label: "Ready to assign",
      colorClass:
        "bg-warning/10 border-warning/40 text-warning",
      description:
        "You have unassigned money remaining. Assign it to categories, savings, or credit card payments to balance the month.",
      amount: v,
    };
  }
  return {
    variant: "balanced",
    label: "All balanced",
    colorClass:
      "bg-success/10 border-success/40 text-success",
    description: "Every peso is assigned. Nice work.",
    amount: 0,
  };
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

    <!-- Status card. Border + tinted bg makes each state read at a
         glance without the user parsing the number. -->
    <section
      class="rounded-lg border p-4 space-y-2"
      :class="status.colorClass"
    >
      <div class="flex items-center justify-between">
        <span class="text-sm font-semibold uppercase tracking-wide">
          {{ $t(status.label) }}
        </span>
        <span class="text-xl font-bold tabular-nums">
          {{ formatMoney(status.amount) }}
        </span>
      </div>
      <p class="text-xs opacity-90 leading-relaxed">
        {{ $t(status.description) }}
      </p>
    </section>

    <!-- Summary card — aggregate totals across all groups. -->
    <section class="rounded-lg bg-base-lvl-3 border border-base p-4">
      <h4 class="text-xs font-semibold text-body-1 uppercase tracking-wide mb-3">
        {{ $t('Summary') }}
      </h4>
      <div class="space-y-1 text-sm">
        <div
          class="flex justify-between items-center py-2 px-3 rounded-md bg-base-lvl-2"
        >
          <span class="font-medium text-body">{{ $t('Assigned') }}</span>
          <span class="font-bold text-body tabular-nums">
            {{ formatMoney(totals.assigned) }}
          </span>
        </div>
        <div class="flex justify-between items-center py-2 px-3">
          <span class="text-body-1">{{ $t('Outflow') }}</span>
          <span class="tabular-nums text-body-1">
            {{ formatMoney(totals.activity) }}
          </span>
        </div>
        <div class="flex justify-between items-center py-2 px-3">
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
