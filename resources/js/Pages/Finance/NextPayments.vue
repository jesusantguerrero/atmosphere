<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceSectionNav from "@/Pages/Finance/Partials/FinanceSectionNav.vue";
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import NextPaymentsWidget from "@/domains/transactions/components/NextPaymentsWidget.vue";

import { useTransactionModal, TRANSACTION_DIRECTIONS } from "@/domains/transactions";
import { formatMoney } from "@/utils";
import { ITransaction } from "@/domains/transactions/models";

const props = withDefaults(defineProps<{
    payments: ITransaction[];
    filter?: string;
    sectionTitle?: string;
}>(), {
    payments: () => [],
    filter: "all",
});

const { openTransactionModal } = useTransactionModal();
const { TRANSFER, WITHDRAW } = TRANSACTION_DIRECTIONS;

// Segmented filter. Each option navigates to the same page with the matching
// ?filter, so the server narrows the unified next-payments list identically to
// the dashboard hero that linked here.
const dataStatus = {
    all: { label: "All", value: "/finance/next-payments" },
    overdue: { label: "Overdue", value: "/finance/next-payments?filter=overdue" },
    due_soon: { label: "Due soon", value: "/finance/next-payments?filter=due_soon" },
};

const total = computed(() =>
    (props.payments ?? []).reduce((sum, p) => sum + Number((p as any).total ?? 0), 0)
);

// Mirrors DashboardSummary.handlePay: open the global transaction modal
// pre-filled from the row so submitting it records the payment. Credit-card
// cycles open a TRANSFER into the card; everything else a WITHDRAW on the
// category. The global saved-handler runs router.reload().
const handlePay = (payment: any) => {
    const total = Number(payment.total ?? 0);
    const paid = Number(payment.paid ?? 0);
    const remaining = Math.max(total - paid, 0) || total;
    openTransactionModal({
        mode: payment.account_id ? TRANSFER : WITHDRAW,
        transactionData: {
            counter_account_id: payment.account_id ?? undefined,
            category_id: payment.category_id ?? undefined,
            total: remaining,
            description: payment.description ?? payment.title ?? "",
            date: payment.due_date ?? payment.date ?? undefined,
        },
    });
};

const title = computed(() => {
    if (props.filter === "overdue") return "Overdue payments";
    if (props.filter === "due_soon") return "Payments due soon";
    return "Next Payments";
});
</script>

<template>
    <AppLayout :title="$t('Next Payments')" @back="router.visit('/')" :show-back-button="true">
        <template #header>
            <FinanceSectionNav>
                <template #actions>
                    <StatusButtons
                        :model-value="filter"
                        :statuses="dataStatus"
                        @change="router.visit($event)"
                    />
                </template>
            </FinanceSectionNav>
        </template>

        <main class="px-5 mx-auto mt-8 mb-20 max-w-screen-md sm:px-6 lg:px-8">
            <header class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-bold text-body">{{ $t(title) }}</h2>
                <span
                    v-if="payments.length"
                    class="text-sm font-bold tabular-nums"
                    :class="filter === 'overdue' ? 'text-error' : 'text-body'"
                >
                    {{ formatMoney(total) }}
                </span>
            </header>

            <NextPaymentsWidget
                :payments="payments"
                :hide-title="true"
                @pay="handlePay"
            />
        </main>
    </AppLayout>
</template>
