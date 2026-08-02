<script setup lang="ts">
import { computed, provide, ref, toRefs, onMounted, nextTick } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";
import { startOfMonth } from "date-fns";
import { NPopover } from "naive-ui";

import { AtDropdownLink } from "atmosphere-ui";

import IconClose from "@/Components/icons/IconClose.vue";
import Modal from "@/Components/atoms/Modal.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import JetDropdown from "@/Components/atoms/Dropdown.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerButtonCircle from "@/Components/atoms/LogerButtonCircle.vue";
import PointAlert from "@/Components/atoms/PointAlert.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";

import FinanceTemplate from "./Partials/FinanceTemplate.vue";
import FinanceSectionNav from "./Partials/FinanceSectionNav.vue";
import ExpenseIncome from "@/domains/transactions/components/ExpenseIncome.vue";

import BudgetBalanceAssign from "@/domains/budget/components/BudgetBalanceAssign.vue";
import BudgetDetailForm from "@/domains/budget/components/BudgetDetailForm.vue";
import BudgetSidePanel from "@/domains/budget/components/BudgetSidePanel.vue";
import MonthStripYear from "@/domains/budget/components/MonthStripYear.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import { createBudgetCategory } from "@/domains/budget/createBudgetCategory";

import { useBudget } from "@/domains/budget";
import { SearchFilterMode, useServerSearch } from "@/composables/useServerSearchV2";
import BudgetCategories from "./Partials/BudgetCategories.vue";
import BudgetErrorBanner from "@/domains/budget/components/BudgetErrorBanner.vue";
import BudgetOnboarding from "@/domains/budget/components/BudgetOnboarding.vue";

import { MonthTypeFormat, formatMonth, formatMoney } from "@/utils";
import { ICategory } from "@/domains/transactions/models";

const props = defineProps({
  budgets: {
    type: Array,
    required: true,
  },
  accounts: {
    type: Array,
    default() {
      return [];
    },
  },
  accountTotal: {
    type: Number,
    default: 0
  },
  scheduledTotal: {
    type: Number,
    default: 0
  },
  categories: {
    type: Array,
    required: true,
  },
  serverSearchOptions: {
    type: Object,
    default: () => ({}),
  },
});

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay, toggleCustomFilter, setPreventWatch } = useServerSearch(
  serverSearchOptions,
  {
    manual: false,
    defaultDates: true,
  }
);

provide("pageState", pageState);

const sectionTitle = computed(() => {
  return pageState.dates.startDate ? `${formatMonth(pageState.dates.startDate, MonthTypeFormat.monthYear)}` : '--';
});

const { budgets } = toRefs(props);
const {
  readyToAssign,
  filterGroups,
  filters,
  visibleFilters,
  data: categories,
  setBudgetFilter,
  selectedBudget,
  setSelectedBudget,
} = useBudget();

provide("readyToAssign", readyToAssign);

const panelSize = computed(() => {
  return !selectedBudget.value ? "large" : "large";
});

const { isSmaller } = useBreakpoints(breakpointsTailwind);
const showCategoriesInMain = isSmaller("md");

//  budget filters
const budgetStatus = {
  funded: {
    label: "Funded",
  },
  underFunded: {
    label: "Not funded",
  },
};

const currentStatus = computed(() =>
    pageState?.custom?.mode
);

provide("categories", categories);

//  Budget Form
const deleteBudget = (budget: ICategory) => {
  router.delete(route("budgets.destroy", budget), {
    onSuccess: () => {
      router.reload({
        only: ["budgets"]
      });
    },
  });
};
const onBudgetItemSaved = () => {};

const goToday = () => {
  pageState.dates.startDate = startOfMonth(new Date());
  executeSearchWithDelay();
};

// "Add category group" affordance in the header toolbar. Popover
// triggered by IMdiPlus opens an inline input; Enter saves, Esc
// cancels. Preserves the previous 3rd-row placement's functionality
// in a single-icon footprint that fits the 2-row header.
const groupForm = useForm({
  account_id: null,
  parent_id: null,
  name: "",
  amount: 0,
});
const showAddGroupPopover = ref(false);
const groupInputRef = ref<HTMLInputElement | null>(null);

const openAddGroup = async () => {
  showAddGroupPopover.value = true;
  await nextTick();
  groupInputRef.value?.focus?.();
};

const cancelAddGroup = () => {
  showAddGroupPopover.value = false;
  groupForm.name = "";
};

const submitAddGroup = () => {
  const name = groupForm.name.trim();
  if (!name) {
    cancelAddGroup();
    return;
  }
  createBudgetCategory(groupForm, undefined, () => {
    groupForm.name = "";
    showAddGroupPopover.value = false;
  });
};

const monthIsEmpty = computed(() => {
    const groups = (categories.value ?? []) as any[];
    for (const group of groups) {
        for (const cat of group.subCategories ?? []) {
            if (Number(cat.budgeted) > 0) {
                return false;
            }
        }
    }
    return true;
});

const currentMonthIso = computed(() => {
    const start = pageState.dates.startDate;
    if (!start) {
        return null;
    }
    const date = start instanceof Date ? start : new Date(start);
    return startOfMonth(date).toISOString().slice(0, 10);
});

const isConfirmingCopyOverwrite = ref(false);

const performCopyFromPrevious = (overwrite: boolean) => {
    if (!currentMonthIso.value) return;
    router.post(
        `/budgets/months/${currentMonthIso.value}/copy-from-previous`,
        { overwrite },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['budgets'] });
            },
        }
    );
};

const copyFromPrevious = () => {
    if (!currentMonthIso.value) return;
    if (!monthIsEmpty.value) {
        // Existing plan present — gate the destructive overwrite behind a styled modal.
        isConfirmingCopyOverwrite.value = true;
        return;
    }
    performCopyFromPrevious(false);
};

const confirmCopyOverwrite = () => {
    isConfirmingCopyOverwrite.value = false;
    performCopyFromPrevious(true);
};

const toggleFilter = async (value: string) => {
    setBudgetFilter(value)
    const status = Object.keys(filters.value).find((key) => filters.value[key]) ?? "";
    toggleCustomFilter('mode', status, SearchFilterMode.Replace, false);
};

onMounted(() => {
    nextTick(() => {
        toggleFilter(currentStatus.value ?? "");
    })
})

const readyToAssignBalance = computed(() => {
    return readyToAssign.value.balance
})

const readyToAssignLeft = computed(() => {
    return readyToAssign.value.toAssign
})

// Fix (Hope): when money is still available to assign (RTA balance > 0),
// "overspent" categories are really just *unfunded* — the money exists, it
// just hasn't been assigned yet. Don't alarm the surplus-first user with red;
// only a true overspend (nothing left to cover) is an alert.
const overspentIsCovered = computed(() => Number(readyToAssignBalance.value ?? 0) > 0);

const budgetCsvExportUrl = computed(() => {
    const { startDate } = pageState.dates;
    if (startDate) {
        const month = startDate instanceof Date
            ? startDate.toISOString().slice(0, 7) + '-01'
            : String(startDate).slice(0, 7) + '-01';
        return `${route('budget.export-csv')}?month=${month}`;
    }
    return route('budget.export-csv');
})
</script>

<template>
  <AppLayout
    :title="sectionTitle"
    @back="router.visit(route('finance'))"
    :show-back-button="true"
  >
    <template #header>
      <FinanceSectionNav />
    </template>

    <FinanceTemplate :accounts="accounts" :panel-size="panelSize" dense>
      <!--
        Newcomer onboarding: replaces the old generic "This is your budget"
        MessageBox with an actionable 3-step walkthrough. Auto-hides once the
        user assigns their first peso this month (monthIsEmpty flips false).
        Dismiss persists in localStorage for returning power-users who land on
        a fresh month with no assignments yet — they don't need to see it again.
      -->
      <BudgetOnboarding
        :month-is-empty="monthIsEmpty"
        :ready-to-assign="Number(readyToAssignBalance) || 0"
        :has-accounts="(accounts?.length ?? 0) > 0"
      />

      <!-- Row 1: Month strip. Extracted from BudgetBalanceAssign's #top slot
           so it renders standalone above the consolidated toolbar row. -->
      <MonthStripYear
        v-if="pageState.dates?.startDate"
        class="w-full"
        v-model:startDate="pageState.dates.startDate"
        v-model:endDate="pageState.dates.endDate"
        @change="executeSearchWithDelay(5)"
      />

      <!--
        Row 2 — consolidated header (Actual-style). Outer container
        mirrors the row anatomy so labels align pixel-exact with values
        below: px-4 (matches BudgetGroupItem/BudgetItem left+right
        padding), left flex-1 group holds toolbar + assign pill, right
        group is fixed w-36/w-44/w-28/w-8 no gap. Previously ml-auto +
        internal px-4 on the labels div was double-padding + pushing
        content past the section's right edge (AVAILABLE got cut off).
      -->
      <div class="flex items-center gap-2 px-4 mt-2 py-1">
        <!-- Left group: toolbar + assign pill share flex-1, filling the
             equivalent of the row's "name" area. Icons-only toolbar in
             the style of Actual: filter (opens the Funded/Not-funded
             popover), today (jump to current month), more-actions (⋮).
             The Overspent chip stays as a text banner when triggered
             because it's a contextual alert, not a permanent control. -->
        <div class="flex-1 min-w-0 flex flex-wrap md:flex-nowrap items-center gap-1">
          <AtButton
            v-if="visibleFilters.overspent"
            @click="toggleFilter('overspent')"
            class="flex items-center justify-between space-x-2 rounded-md min-w-fit group"
            :class="[
              filters.overspent
                ? (overspentIsCovered ? 'bg-warning text-white' : 'bg-primary text-white')
                : (overspentIsCovered ? 'text-warning' : 'text-primary')
            ]"
          >
            <span class="relative">
              {{ filterGroups.overSpent.length }} {{ overspentIsCovered ? $t('categories to fund') : $t('overspent categories') }}
              <PointAlert v-if="!filters.overspent && !overspentIsCovered" />
            </span>

            <div class="text-white text-sm rounded-full group-hover:bg-base-lvl-3/20 p-0.5">
              <IconClose />
            </div>
          </AtButton>

          <!-- Filter icon opens a popover with the Funded/Not-funded
               segmented control. Active-state dot appears on the icon
               when a filter is set so users can tell at a glance. -->
          <NPopover trigger="click" placement="bottom-start">
            <template #trigger>
              <LogerButtonCircle
                :title="$t('Filter')"
                class="relative"
              >
                <IMdiFilterVariant />
                <span
                  v-if="currentStatus"
                  class="absolute top-0 right-0 w-1.5 h-1.5 rounded-full bg-primary"
                  aria-hidden="true"
                />
              </LogerButtonCircle>
            </template>
            <div class="p-2">
              <StatusButtons
                :modelValue="currentStatus"
                :statuses="budgetStatus"
                @change="toggleFilter"
              />
            </div>
          </NPopover>

          <LogerButtonCircle
            :title="$t('Jump to current month')"
            @click="goToday"
          >
            <IMdiTargetVariant />
          </LogerButtonCircle>

          <!-- Add category group — popover with inline input. Removed
               the previous 3rd row that hosted this affordance so the
               header stays at 2 rows like Actual. -->
          <NPopover
            v-model:show="showAddGroupPopover"
            trigger="manual"
            placement="bottom-start"
          >
            <template #trigger>
              <LogerButtonCircle
                :title="$t('Add category group')"
                @click="openAddGroup"
              >
                <IMdiPlus />
              </LogerButtonCircle>
            </template>
            <div class="p-2 w-64 space-y-2">
              <label class="block text-xs font-medium text-body-1">
                {{ $t('Category group name') }}
              </label>
              <LogerInput
                ref="groupInputRef"
                v-model="groupForm.name"
                :placeholder="$t('e.g. Vivienda, Comida')"
                :disabled="groupForm.processing"
                class="text-sm"
                @keydown.enter="submitAddGroup"
                @keydown.esc="cancelAddGroup"
              />
              <div class="flex items-center justify-end gap-2 pt-1">
                <button
                  type="button"
                  class="text-xs text-body-1 hover:text-body px-2 py-1"
                  @click="cancelAddGroup"
                >
                  {{ $t('Cancel') }}
                </button>
                <button
                  type="button"
                  class="text-xs font-medium px-3 py-1 rounded-md bg-primary text-white disabled:opacity-50"
                  :disabled="!groupForm.name.trim() || groupForm.processing"
                  @click="submitAddGroup"
                >
                  {{ $t('Save') }}
                </button>
              </div>
            </div>
          </NPopover>

          <!-- The ⋮ More Actions dropdown (Copy from last month, Export
               CSV, Export Budget) moved to BudgetSidePanel's hamburger
               menu on the right so those month-level actions live with
               the rest of the budget-summary controls. -->

          <BudgetBalanceAssign
            class="rounded-md shrink-0"
            :value="readyToAssignBalance"
            :category="readyToAssignLeft"
            :to-assign="readyToAssign"
            :scheduled-total="scheduledTotal"
          />
        </div>

        <!-- Right group: fixed w-36/w-44/w-28/w-8 no gap. Mirrors
             BudgetGroupItem/BudgetItem right-side anatomy 1:1, and
             because the outer container has the same px-4 as the rows,
             the labels sit pixel-aligned above the values below. No
             internal padding or ml-auto — flex-1 on the left group
             pushes this to the right naturally. -->
        <!-- SPENT uses text-left pl-8 (not text-right like the others)
             because ExpenseChartWidgetRow renders its value with
             `inline-flex justify-between px-4` + a `ml-4` on the value
             span → the "DOP X.XX" starts 32px from the left edge of the
             w-44 chart column. Mimicking that offset here puts the
             SPENT label directly above its value; text-right would leave
             a ~90px visual gap on the right side of the column. -->
        <div class="hidden md:flex items-center flex-nowrap shrink-0 text-xs uppercase tracking-wide text-body-1/50 font-medium">
          <span class="w-36 text-right">{{ $t('Assigned') }}</span>
          <span class="w-44 text-left pl-8">{{ $t('Spent') }}</span>
          <span class="w-28 text-right">{{ $t('Available') }}</span>
          <span class="w-8" aria-hidden="true"></span>
        </div>
      </div>

      <!-- data-budget-table anchor is used by BudgetOnboarding's "Jump to
           categories" CTA to smooth-scroll here. max-w-7xl was previously
           capping the table around ~1280px, leaving huge empty lateral space
           on wider screens — the assign card and column headers already fit
           any width, so let the table breathe. -->
      <section data-budget-table class="w-full mt-4 rounded-lg text-body bg-base">
          <article class="w-full space-y-4">
            <BudgetErrorBanner />
            <BudgetCategories :budgets="budgets" />
        </article>
      </section>

      <template #prepend-panel>
        <!--
          Actual-style right panel: persistent "My Budget" section with
          Status + Summary cards + a hamburger menu that consolidates
          the month-level actions (previously scattered in the header
          toolbar's ⋮ dropdown). The selected-category BudgetDetailForm
          slots in below when a row is selected; otherwise the panel
          just shows the general budget health. Replaces the old
          ExpenseIncome fallback (Income vs Expense chart) — the same
          spend data is available from the Trends page and from the
          per-row activity chart, so the panel real estate is better
          spent on aggregate summary + quick actions.
        -->
        <BudgetSidePanel
          :value="readyToAssignBalance"
          :to-assign="readyToAssign"
          :budgets="budgets"
          :budget-csv-export-url="budgetCsvExportUrl"
          :export-budget-url="route('budget.export')"
          @copy-from-previous="copyFromPrevious()"
        >
          <template #detail>
            <BudgetDetailForm
              v-if="selectedBudget && !showCategoriesInMain"
              full
              :category="selectedBudget"
              :item="selectedBudget.budget"
              :editable="true"
              @saved="onBudgetItemSaved"
              @deleted="deleteBudget"
              @cancel="setSelectedBudget()"
              @close="setSelectedBudget()"
            />
          </template>
        </BudgetSidePanel>
      </template>
    </FinanceTemplate>
    <modal
      :show="selectedBudget && showCategoriesInMain"
      max-width="mobile"
      :closeable="true"
      @close="setSelectedBudget()"
    >
      <BudgetDetailForm
        full
        v-model:category="selectedBudget"
        :item="selectedBudget.budget"
        @saved="onBudgetItemSaved"
        @deleted="deleteBudget"
        @cancel="setSelectedBudget()"
        @close="setSelectedBudget()"
      />
    </modal>

    <ConfirmationModal
        :show="isConfirmingCopyOverwrite"
        :title="$t('Overwrite this month\'s plan?')"
        @close="isConfirmingCopyOverwrite = false"
    >
        <template #content>
            <p>{{ $t('This month already has assignments.') }}</p>
            <p class="mt-2 text-sm text-body-1/70">
                {{ $t('Copying from last month will replace the current amounts. This cannot be undone.') }}
            </p>
        </template>
        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <LogerButton variant="neutral" rounded @click="isConfirmingCopyOverwrite = false">
                    {{ $t('Cancel') }}
                </LogerButton>
                <LogerButton variant="error" rounded @click="confirmCopyOverwrite">
                    {{ $t('Overwrite') }}
                </LogerButton>
            </div>
        </template>
    </ConfirmationModal>
  </AppLayout>
</template>

<style>
.budget-right-panel {
  display: grid;
  grid-template-rows: 50px calc(100vh - 420px) 150px;
  gap: 8px;
}
</style>
