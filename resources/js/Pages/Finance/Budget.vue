<script setup lang="ts">
import { computed, provide, ref, toRefs, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import { AtButton, AtDatePager } from "atmosphere-ui";
import { useBreakpoints, breakpointsTailwind } from "@vueuse/core";
import { startOfMonth } from "date-fns";

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

import { useBudget } from "@/domains/budget";
import { SearchFilterMode, useServerSearch } from "@/composables/useServerSearchV2";
import MessageBox from "@/Components/organisms/MessageBox.vue";
import BudgetCategories from "./Partials/BudgetCategories.vue";
import BudgetErrorBanner from "@/domains/budget/components/BudgetErrorBanner.vue";

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
      <FinanceSectionNav>
        <template #actions>
          <div class="flex items-center space-x-2">
            <!-- Overspent notice -->
            <AtButton
              v-if="visibleFilters.overspent"
              @click="toggleFilter('overspent')"
              class="flex items-center justify-between space-x-2 rounded-md min-w-fit group"
              :class="[filters.overspent ? 'bg-primary text-white' : 'text-primary']"
            >
              <span class="relative">
                {{ filterGroups.overSpent.length }} Overspent categories
                <PointAlert v-if="!filters.overspent" />
              </span>

              <div class="text-white text-sm rounded-full group-hover:bg-white/20 p-0.5">
                <IconClose />
              </div>
            </AtButton>
            <StatusButtons
              :modelValue="currentStatus"
              :statuses="budgetStatus"
              @change="toggleFilter"
            />

            <LogerButton variant="inverse" @click="goToday"> {{ $t('Today') }} </LogerButton>

            <JetDropdown align="right" width="48">
                <template #trigger>
                    <LogerButtonCircle :title="$t('More actions')">
                        <IMdiDotsVertical />
                    </LogerButtonCircle>
                </template>

                <template #content>
                    <div class="w-56 py-1">
                        <AtDropdownLink
                            as="button"
                            @click="copyFromPrevious()"
                        >
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

                        <AtDropdownLink :href="route('budget.export')" target="_blank" as="a">
                            <section class="flex items-center w-full">
                                <IMdiExport class="mr-2" />
                                <span>{{ $t('Export') }} {{ $t('Budget') }}</span>
                            </section>
                        </AtDropdownLink>
                    </div>
                </template>
            </JetDropdown>
          </div>
        </template>
      </FinanceSectionNav>
    </template>

    <FinanceTemplate :accounts="accounts" :panel-size="panelSize">
      <!-- Budget intro tutorial — dismissable. Storage key is stable across locales
           so dismissing in Spanish doesn't re-surface the box when switched to English. -->
      <MessageBox
        storage-key="loger-budget-intro-dismissed"
        :title="$t('This is your budget.')"
        :content="$t('Create new category groups and categories and organize them to suit your needs')"
      />

      <BudgetBalanceAssign
        class="rounded-t-md"
        :class="[!visibleFilters.overspent && 'rounded-b-md']"
        :value="readyToAssignBalance"
        :category="readyToAssignLeft"
        :to-assign="readyToAssign"
        :scheduled-total="scheduledTotal"
      >
        <template #top>
            <AtDatePager
              v-if="pageState.dates?.startDate"
              class="w-full h-12 border-none bg-base-lvl-1 text-body"
              v-model:startDate="pageState.dates.startDate"
              v-model:endDate="pageState.dates.endDate"
              @change="executeSearchWithDelay(5)"
              controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
              next-mode="month"
            >
              {{ formatMonth(pageState.dates.startDate, "MMMM") }}
            </AtDatePager>
        </template>
      </BudgetBalanceAssign>

      <section class="mx-auto mt-4 rounded-lg text-body bg-base max-w-7xl">
          <article class="w-full space-y-4">
            <BudgetErrorBanner />
            <BudgetCategories :budgets="budgets" />
        </article>
      </section>

      <template #prepend-panel class="">
        <div class="space-y-4 ">
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

          <ExpenseIncome
            v-else
            :value="readyToAssign.inflow + readyToAssign.budgetedSpending"
            :footer-stats="[
              {
                label: 'Income',
                value: readyToAssign.inflow,
                class: 'text-success',
              },
              {
                label: 'Expense',
                value: readyToAssign.budgetedSpending,
                class: 'text-error',
              },
            ]"

            />
        </div>
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
