<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useSessionStorage } from "@vueuse/core";

import AppLayout from "@/Components/templates/AppLayout.vue";
import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
import AppIcon from "@/Components/AppIcon.vue";

// DashboardSummary is now the single Dashboard view. The previous
// Summary/Detailed toggle was removed once the summary widget matured
// enough to cover every daily-use surface — first impression and
// power-user use both land on the same layout, avoiding the
// duplicated-info problem the two views had.
import DashboardSummary from "./Partials/DashboardSummary.vue";
// Type-only imports for props typing; the underlying widgets themselves
// no longer render on Dashboard, but the shape contract for the payload
// stayed identical, so we import the types without pulling the components.
import type { TodayItem } from "./Partials/DueTodayWidget.vue";
import type { UpcomingItem } from "./Partials/UpcomingWidget.vue";

import BulkSelectionBar from "@/Components/BulkSelectionBar.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import WelcomeModal from "@/Components/organisms/WelcomeModal.vue";

import { useAppContextStore } from "@/store";
import { useTransactionStore } from "@/store/transactions";
import { IOccurrenceCheck } from "@/domains/housing/models";
import { IAccount, ICategory, ITransaction } from "@/domains/transactions/models";
import { IBudgetStat } from "@/domains/budget/models/budget";
import { useModuleEnabled } from '@/domains/app'

const props = withDefaults(
  defineProps<{
    spendingSummary: {
      previousYear: {
        values: [];
      };
      currentYear: {
        values: [];
      };
    };
    drafts?: number;
    expenses: {
      previousYear: {
        values: [];
      };
      currentYear: {
        values: [];
      };
    };
    meals: { data: any[] };
    user: {
      name: string;
      current_team_id: number;
    };
    netWorth: any;
    budgetTotal: IBudgetStat[];
    nextPayments: ITransaction[];
    transactionTotal: Record<string, any>;
    categories: ICategory[];
    accounts: IAccount[];
    onboarding: Record<string, any>;
    checks?: IOccurrenceCheck[];
    modules: any[];
    topWatchlists: any[];
    /** From /today route (now merged here). Action list of items due today. */
    todayItems?: TodayItem[];
    /** From /today route. Cross-pillar timeline including planner items. */
    upcomingItems?: UpcomingItem[];
  }>(),
  {
    todayItems: () => [],
    upcomingItems: () => [],
  }
);
const contextStore = useAppContextStore();

const { isModuleEnabled } = useModuleEnabled(props.modules)

const areChecksLoading = ref(true);

interface DynamicStore  {
    checks: IOccurrenceCheck[];
    drafts: number
}

const dynamicStore = useSessionStorage<DynamicStore>(`dynamic-store::${props.user?.current_team_id ?? 'default'}`,{
    checks: [],
    drafts: 0
})
const fetchChecks = () => {
  return router.reload({
    only: ["checks", 'drafts'],
    onFinish: () => {
      areChecksLoading.value = false;
      dynamicStore.value.checks = props.checks;
      dynamicStore.value.drafts = props.drafts
    },
  });
};

onMounted(() => {
  fetchChecks();
});

const selectedItems = ref([]);
const deleteTransactionsForm = useForm({
  isVisible: false,
  data: [],
});

const transactionStore = useTransactionStore();
const deleteBulkTransactions = () => {
  deleteTransactionsForm
    .transform(() => ({
      data: selectedItems.value,
    }))
    .post(`/finance/transactions/bulk/delete`, {
      onSuccess() {
        deleteTransactionsForm.isVisible = false;
        selectedItems.value = [];
        router.reload();
        transactionStore.reload();
      },
    });
};
</script>

<template>
  <AppLayout :title="$t('Dashboard')">
    <template #title v-if="contextStore.isMobile">
      <AppIcon size="medium" class="ml-2" />
    </template>

    <main class="px-5 mx-auto mt-5 mb-10 max-w-screen-2xl sm:px-6 lg:px-8">
      <!-- Welcome heading (desktop only; mobile shows the app icon in the title slot). -->
      <div class="flex items-center justify-between mt-4 mb-4" v-if="!contextStore.isMobile">
        <h1 class="text-lg font-bold text-body">
            {{ $t('dashboard.welcome') }} <span class="text-primary">{{ user?.name }}</span>
        </h1>
      </div>

      <!-- Onboarding steps sit above the summary so newcomers see them first. -->
      <OnboardingSteps
        v-if="onboarding.steps"
        class="mb-4"
        :steps="onboarding.steps"
        :percentage="onboarding.percentage"
      />

      <!-- Single Dashboard view. The previous Summary/Detailed toggle was
           dropped once the summary widget covered every daily-use surface;
           the detailed layout duplicated info that already lived inside
           DashboardSummary and split power-user attention across two
           versions of the same numbers. -->
      <DashboardSummary
        :net-worth="netWorth"
        :expenses="transactionTotal.total_amount"
        :accounts="accounts"
        :budget-total="budgetTotal"
        :next-payments="nextPayments"
        :checks="dynamicStore.checks"
        :meals="meals"
        :user="user"
        :top-watchlists="topWatchlists"
        :is-meals-enabled="isModuleEnabled('meals')"
        :is-housing-enabled="isModuleEnabled('housing')"
        :today-items="todayItems"
        :drafts="dynamicStore.drafts"
      />
    </main>

    <!-- DashboardFab removed: the global MobileMenuBar already exposes a +
         FAB for the add-transaction action, and stacking two pink FABs in
         the bottom-right corner created visual duplication. The quick-
         action expansion (Expense / Income / Transfer) lives in this
         component and can be re-introduced behind a long-press gesture or
         a single visible FAB if/when users ask for it. -->
    <!-- <DashboardFab /> -->

    <BulkSelectionBar
      v-if="selectedItems.length"
      :selected-items="selectedItems"
      @delete-pressed="deleteTransactionsForm.isVisible = true"
    />
    <WelcomeModal :modules="modules" />

    <ConfirmationModal
      :show="deleteTransactionsForm.isVisible"
      @close="deleteTransactionsForm.isVisible = false"
      title="Delete transactions"
      content="Once transactions are deleted, all of its resources and data will be permanently deleted."
    >
      <template #footer>
        <footer class="flex justify-end">
          <LogerButton
            @click="deleteTransactionsForm.isVisible = false"
            variant="neutral"
          >
            Cancel
          </LogerButton>

          <LogerButton
            type="danger"
            class="ml-2"
            @click="deleteBulkTransactions"
            :class="{ 'opacity-25': deleteTransactionsForm.processing }"
            :disabled="deleteTransactionsForm.processing"
          >
            Delete Transactions
          </LogerButton>
        </footer>
      </template>
    </ConfirmationModal>
  </AppLayout>
</template>
