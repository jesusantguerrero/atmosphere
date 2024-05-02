<script setup lang="ts">
import { ref, onMounted } from "vue";
import { router, useForm } from "@inertiajs/vue3";

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
import BulkSelectionBar from "@/Components/BulkSelectionBar.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import { useAppContextStore } from "@/store";
import { useTransactionStore } from "@/store/transactions";
import { IOccurrenceCheck } from "@/domains/housing/models";
import { IAccount, ICategory } from "@/domains/transactions/models";
import { IBudgetStat } from "@/domains/budget/models";

withDefaults(
  defineProps<{
    spendingSummary: {
      previousYear: {
        values: [];
      };
      currentYear: {
        values: [];
      };
    };
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
    };
    netWorth: any;
    budgetTotal: IBudgetStat;
    nextPayments: any[];
    transactionTotal: Record<string, any>;
    categories: ICategory[];
    accounts: IAccount[];
    onboarding: Record<string, any>;
    checks: IOccurrenceCheck[];
  }>(),
  {}
);
const contextStore = useAppContextStore();

const selected = ref(null);

const AccountsTrackerRef = ref();

const areChecksLoading = ref(true);
const fetchChecks = () => {
  return router.reload({
    only: ["checks"],
    onFinish: () => {
      areChecksLoading.value = false;
    },
  });
};

onMounted(() => {
  fetchChecks();
});

const transactionsTabs = [
  {
    name: "next",
    label: "Next",
  },
  {
    name: "drafts",
    label: "Drafts",
  },
];

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
        router.reload({ preserveScroll: true });
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

    <main
      class="px-5 mx-auto mt-5 mb-10 md:space-y-0 md:space-x-10 md:flex max-w-screen-2xl sm:px-6 lg:px-8"
    >
      <section class="mt-6 md:w-9/12 space-y-4">
        <section class="flex flex-col md:flex-row md:space-x-4">
          <AccountsTracker
            class="md:w-7/12 w-full order-1 mt-2 md:mt-0"
            ref="AccountsTrackerRef"
            :net-worth="netWorth"
            :expenses="transactionTotal.total_amount"
            :message="$t('dashboard.welcome')"
            :username="user.name"
            @section-click="selected = $event"
          />
          <BudgetFundWidget class="md:w-5/12 w-full order-1 mt-2 md:mt-0" />
        </section>

        <DashboardSpending :expenses="expenses" :spending-summary="spendingSummary" />
        <MealWidget :meals="meals?.data" />
      </section>
      <section class="py-6 space-y-4 md:w-3/12">
        <OccurrenceWidget :checks="checks" :wrap="true" />

        <OnboardingSteps
          v-if="onboarding.steps"
          class="mt-5"
          :steps="onboarding.steps"
          :percentage="onboarding.percentage"
        />
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

            <DashboardDrafts v-else v-model:selected="selectedItems" />
          </template>
        </WidgetContainer>
        <BudgetWidget :budget="budgetTotal" />
        <!-- <NetWorthWidget
                    ref="NetWorthWidgetRef"
                    :budget="budgetTotal"
                    :message="$t('Net-worth trend')"
                    @section-click="selected=$event"
                /> -->
      </section>
    </main>
    <BulkSelectionBar
      v-if="selectedItems.length"
      :selected-items="selectedItems"
      @delete-pressed="deleteTransactionsForm.isVisible = true"
    />
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
