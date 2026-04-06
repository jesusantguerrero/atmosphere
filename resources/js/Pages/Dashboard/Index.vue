<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { useSessionStorage, useLocalStorage } from "@vueuse/core";

import AppLayout from "@/Components/templates/AppLayout.vue";
import OnboardingSteps from "@/Components/widgets/OnboardingSteps.vue";
import AppIcon from "@/Components/AppIcon.vue";
import DashboardDrafts from "./Partials/DashboardDrafts.vue";
import WidgetContainer from "@/Components/WidgetContainer.vue";

import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";
import MealWidget from "@/domains/meal/components/MealWidget.vue";
import AccountsTracker from "@/domains/transactions/components/AccountsTracker.vue";
import OccurrenceWidget from "@/domains/housing/components/OccurrenceWidget.vue";
import DashboardSpending from "./Partials/DashboardSpendings.vue";
import BudgetFundWidget from "./Partials/BudgetFundWidget.vue";
import BudgetWidget from "./Partials/BudgetWidget.vue";
import AccountBalancesWidget from "./Partials/AccountBalancesWidget.vue";
import DashboardSummary from "./Partials/DashboardSummary.vue";
import DashboardFab from "./Partials/DashboardFab.vue";
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
  }>(),
  {}
);
const contextStore = useAppContextStore();

const { isModuleEnabled } = useModuleEnabled(props.modules)

const dashboardView = useLocalStorage<'summary' | 'detailed'>('loger-dashboard-view', 'summary');

const selected = ref(null);

const AccountsTrackerRef = ref();

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

const transactionsTabs = computed(() => [
  {
    name: "next",
    label: "Next",
  },
  {
    name: "drafts",
    label: "Drafts",
    count: dynamicStore.value.drafts
  },
]);

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
  <AppLayout>
    <template #title v-if="contextStore.isMobile">
      <AppIcon size="medium" class="ml-2" />
    </template>

    <main class="px-5 mx-auto mt-5 mb-10 max-w-screen-2xl sm:px-6 lg:px-8">
      <!-- View toggle -->
      <div class="flex items-center justify-between mt-4 mb-4">
        <h1 class="text-lg font-bold text-body" v-if="!contextStore.isMobile">
            {{ $t('dashboard.welcome') }} <span class="text-primary">{{ user?.name }}</span>
        </h1>
        <div class="flex gap-1 bg-base-lvl-2 rounded-lg p-0.5 ml-auto">
          <button
              @click="dashboardView = 'summary'"
              class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
              :class="dashboardView === 'summary'
                  ? 'bg-base-lvl-3 text-body shadow-sm'
                  : 'text-body-1/60 hover:text-body-1'"
          >
              {{ $t('Summary') }}
          </button>
          <button
              @click="dashboardView = 'detailed'"
              class="px-3 py-1 text-xs font-medium rounded-md transition-colors"
              :class="dashboardView === 'detailed'
                  ? 'bg-base-lvl-3 text-body shadow-sm'
                  : 'text-body-1/60 hover:text-body-1'"
          >
              {{ $t('Detailed') }}
          </button>
        </div>
      </div>

      <!-- Onboarding (always on top, both views) -->
      <OnboardingSteps
        v-if="onboarding.steps"
        class="mb-4"
        :steps="onboarding.steps"
        :percentage="onboarding.percentage"
      />

      <!-- SUMMARY VIEW -->
      <DashboardSummary
        v-if="dashboardView === 'summary'"
        :net-worth="netWorth"
        :expenses="transactionTotal.total_amount"
        :accounts="accounts"
        :budget-total="budgetTotal"
        :next-payments="nextPayments"
        :checks="dynamicStore.checks"
        :meals="meals"
        :user="user"
        :is-meals-enabled="isModuleEnabled('meals')"
        :is-housing-enabled="isModuleEnabled('housing')"
      />

      <!-- DETAILED VIEW (original layout) -->
      <div v-else class="md:space-y-0 md:space-x-10 md:flex">
        <section class="mt-2 md:w-9/12 space-y-4">
          <section class="flex flex-col md:flex-row md:space-x-4">
            <AccountsTracker
              class="md:w-7/12 w-full order-1 mt-2 md:mt-0"
              ref="AccountsTrackerRef"
              :net-worth="netWorth"
              :expenses="transactionTotal.total_amount"
              :message="$t('dashboard.welcome')"
              :username="user?.name"
              @section-click="selected = $event"
            />
            <BudgetFundWidget class="md:w-5/12 w-full order-1 mt-2 md:mt-0" />
          </section>

          <DashboardSpending :expenses="expenses" :spending-summary="spendingSummary" />
          <MealWidget :meals="meals?.data" v-if="isModuleEnabled('meals')" />
        </section>
        <section class="py-6 space-y-4 md:w-3/12">
          <OccurrenceWidget
              :checks="dynamicStore.checks"
              :wrap="true"
              v-if="isModuleEnabled('housing')" />

          <AccountBalancesWidget :accounts="accounts" />
          <WidgetContainer
            :message="$t('Transactions')"
            :tabs="transactionsTabs"
            default-tab="next"
            class="order-2 mt-4 lg:mt-0 lg:order-1"
          >
            <template #actions>
              <div id="transaction-actions" />
            </template>
            <template v-slot:content="{ selectedTab }">
              <NextPaymentsWidget
                v-if="selectedTab == 'next'"
                class="w-full"
                :payments="nextPayments"
              />

              <DashboardDrafts
                  v-else
                  v-model:selected="selectedItems"
                  @re-loaded="fetchChecks"
              />
            </template>
          </WidgetContainer>
          <BudgetWidget :budgets="budgetTotal" />
        </section>
      </div>
    </main>

    <DashboardFab />

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
