<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useTransactionModal, TRANSACTION_DIRECTIONS } from "@/domains/transactions";

import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import BudgetProgress from "@/domains/budget/components/BudgetProgress.vue";
import TodayAgendaWidget from "./TodayAgendaWidget.vue";
import MealWidget from "@/domains/meal/components/MealWidget.vue";
import DueTodayWidget, { type TodayItem } from "./DueTodayWidget.vue";

import { getDayDiff } from "@/utils";
import { IAccount, ITransaction } from "@/domains/transactions/models";
import { IBudgetStat } from "@/domains/budget/models/budget";
import { IOccurrenceCheck } from "@/domains/housing/models";

const props = defineProps<{
    netWorth: unknown[];
    income: number | string;
    agendaDate: string;
    agendaEvents: any[];
    expenses: number | string;
    accounts: IAccount[];
    budgetTotal: IBudgetStat[];
    nextPayments: ITransaction[];
    checks: IOccurrenceCheck[];
    meals: { data: any[] };
    user: { name: string; current_team_id: number };
    topWatchlists: any[];
    isMealsEnabled: boolean;
    isHousingEnabled: boolean;
    todayItems?: TodayItem[];
    drafts?: number;
    budgetConfigured?: boolean;
}>();


// Reuse the app-wide transaction modal (rendered globally in AppGlobals).
// Recording the transaction there marks the cycle paid and the global
// "saved" handler already runs router.reload(), refreshing this dashboard.
const { openTransactionModal } = useTransactionModal();
const { TRANSFER, WITHDRAW } = TRANSACTION_DIRECTIONS;

// Coerce to Number because the API sends totals as strings (e.g. "0.00").
// Without this, `!"0.00"` evaluates to `false` (non-empty string is truthy),
// the divide-by-zero guard below is bypassed, and the card renders
// "Infinity% spent" — a real regression seen on first-load-without-budget.
const currentBudget = computed(() => ({
    total: Number(props.budgetTotal?.at(-1)?.total ?? 0),
    spending: Number(props.budgetTotal?.at(-1)?.spending ?? 0),
    savings: Number(props.budgetTotal?.at(-1)?.savings ?? 0),
}));

// A budget "exists" if the team has it configured (categories/targets) — even
// when nothing is assigned THIS month yet. The month's assignment total is a
// separate thing (budgetAssigned below); conflating them made a fully set-up
// budget read as "No budget set" the moment a new month rolled over.
const budgetAssigned = computed(() => Number.isFinite(currentBudget.value.total) && currentBudget.value.total > 0);
const hasBudget = computed(() => Boolean(props.budgetConfigured) || budgetAssigned.value);

const spentPercentage = computed(() => {
    if (!budgetAssigned.value) return 0;
    return Math.round((currentBudget.value.spending / currentBudget.value.total) * 100);
});

// balance arrives from the server as a string; `+` would coerce to concat.
const numericBalance = (a: IAccount): number => {
    const value = parseFloat(String(a.balance ?? 0));
    return Number.isFinite(value) ? value : 0;
};

// ---------------------------------------------------------------------------
// "What needs your attention today" hero.
// Aggregates the genuinely time-sensitive signals scattered across the widgets
// below — overdue/upcoming payments, overdue recurring reminders and drafts
// waiting for review — so the real urgency isn't buried among equal-weight
// cards. Everything here is derived from props already passed to the widgets;
// no new server data is required.
// ---------------------------------------------------------------------------

// Whole-day diff from today. Negative => in the past (overdue), 0 => today,
// positive => in the future.
const daysFromToday = (dateStr?: string | null): number | null => {
    if (!dateStr) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const target = new Date(dateStr);
    if (Number.isNaN(target.getTime())) return null;
    target.setHours(0, 0, 0, 0);
    return Math.round((target.getTime() - today.getTime()) / 86400000);
};

const overduePayments = computed(() =>
    (props.nextPayments ?? []).filter(p => {
        const d = daysFromToday((p as any).date);
        return d !== null && d < 0;
    })
);

const dueSoonPayments = computed(() =>
    (props.nextPayments ?? []).filter(p => {
        const d = daysFromToday((p as any).date);
        return d !== null && d >= 0 && d <= 7;
    })
);

// Reminders past their usual cadence (avg + 3d), mirrors OccurrenceWidget's
// "overdue" threshold so the hero and the widget agree.
const overdueReminders = computed(() =>
    (props.checks ?? []).filter(c => {
        const avg = c.avg_days_passed;
        if (!avg || avg <= 0) return false;
        const days = getDayDiff(c.last_date);
        return typeof days === "number" && days >= avg + 3;
    })
);

const draftsCount = computed(() => Number(props.drafts ?? 0));

// The hero shouts (red) only when something is genuinely overdue.
const hasUrgent = computed(() =>
    overduePayments.value.length > 0 || overdueReminders.value.length > 0
);

const hasAttention = computed(() =>
    hasUrgent.value || dueSoonPayments.value.length > 0 || draftsCount.value > 0
);

const priorityPayments = computed(() => [...overduePayments.value, ...dueSoonPayments.value].slice(0, 3));

// ---------------------------------------------------------------------------
// Issue 1 — "Mark as paid" from a next-payment row.
// Mirrors Finance/Account.vue's payCycle(): opens the SAME global transaction
// modal pre-filled from the clicked payment so submitting it records the
// payment/transfer. For credit-card cycles (payment.account_id = the card) we
// open a TRANSFER into that card — the AutoLinkCreditCardPayment listener then
// attaches the resulting Payment to the open cycle. For non-card items (e.g.
// budget reminders that only carry a category) we open a WITHDRAW pre-filled
// with the category. Only pass a fresh object (no synthetic id) so the modal
// stays in "create" mode. The global saved-handler runs router.reload().
// ---------------------------------------------------------------------------
const handlePay = (payment: any) => {
    const total = Number(payment.total ?? 0);
    const paid = Number(payment.paid ?? 0);
    const remaining = Math.max(total - paid, 0) || total;
    const fundingAccountId = props.accounts?.find(a => numericBalance(a) > remaining)?.id;
    openTransactionModal({
        mode: payment.account_id ? TRANSFER : WITHDRAW,
        transactionData: {
            counter_account_id: payment.account_id ?? undefined,
            category_id: payment.category_id ?? undefined,
            total: remaining,
            description: payment.description ?? payment.title ?? "",
            account_id: fundingAccountId,
            date: payment.due_date ?? (payment as any).date ?? undefined,
        },
    });
};

</script>

<template>
    <div class="space-y-4">
        <!-- Hero: what needs your attention today. Prominent block at the very
             top so overdue/upcoming items and drafts to review read as the
             primary signal; the rest of the dashboard is demoted below it. -->
        <section
            class="rounded-xl border p-5"
            :class="hasUrgent
                ? 'border-error/40 bg-error/5'
                : 'border-base bg-base-lvl-3'"
        >
            <h2 class="text-sm font-bold text-body flex items-center gap-2">
                <i
                    class="fa"
                    :class="hasUrgent ? 'fa-triangle-exclamation text-error' : 'fa-circle-check text-success'"
                />
                {{ $t('What needs your attention today') }}
            </h2>

            <div v-if="priorityPayments.length" class="grid gap-2 pt-3">
                <button v-for="payment in priorityPayments" :key="payment.id" type="button" class="flex w-full min-w-0 flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-3 rounded-lg border border-base bg-base-lvl-2 p-3 text-left hover:border-primary/40" @click="handlePay(payment)">
                    <span class="min-w-0 w-full sm:flex-1">
                        <span class="block truncate text-sm font-medium text-body">{{ payment.description || payment.title }}</span>
                        <span class="block text-xs" :class="daysFromToday(payment.date) < 0 ? 'text-error' : 'text-body-1/70'">{{ daysFromToday(payment.date) < 0 ? $t('Overdue Payments') : $t('Upcoming payments') }} · {{ payment.date?.slice(0, 10) }}</span>
                    </span>
                    <span class="flex w-full items-center justify-between gap-2 sm:block sm:w-auto sm:shrink-0 sm:text-right">
                        <span class="block text-sm font-semibold text-body"><MoneyPresenter :value="payment.total" /></span>
                        <span class="block text-xs text-primary">Registrar pago →</span>
                    </span>
                </button>
            </div>
            <button v-if="nextPayments?.length" type="button" class="text-sm text-primary py-2 hover:underline" @click="router.visit('/finance/next-payments')">{{ $t('See all') }} ({{ nextPayments.length }}) →</button>
            <div v-if="overdueReminders.length || draftsCount" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- Overdue reminders -->
                <button
                    v-if="overdueReminders.length"
                    type="button"
                    class="flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-3 px-3 py-2.5 rounded-lg bg-base-lvl-2 border border-error/30 hover:border-error/50 transition text-left"
                    @click="router.visit('/housing/occurrence?overdue=1')"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <i class="fa fa-bell text-error flex-shrink-0" />
                        <span class="text-sm font-semibold text-body truncate">
                            {{ overdueReminders.length }} {{ $t(overdueReminders.length === 1 ? 'overdue reminder' : 'overdue reminders') }}
                        </span>
                    </span>
                    <span class="text-xs font-semibold text-error flex-shrink-0">{{ $t('Review') }} →</span>
                </button>

                <!-- Drafts / transactions to review -->
                <button
                    v-if="draftsCount"
                    type="button"
                    class="flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-3 px-3 py-2.5 rounded-lg bg-base-lvl-2 border border-base hover:border-primary/30 transition text-left"
                    @click="router.visit('/inbox')"
                >
                    <span class="flex items-center gap-2 min-w-0">
                        <i class="fa fa-receipt text-primary flex-shrink-0" />
                        <span class="text-sm font-semibold text-body truncate">
                            {{ draftsCount }} {{ $t(draftsCount === 1 ? 'transaction to review' : 'transactions to review') }}
                        </span>
                    </span>
                    <span class="text-xs font-semibold text-primary flex-shrink-0">{{ $t('Review') }} →</span>
                </button>
            </div>

            <p v-if="!hasAttention" class="mt-2 text-sm text-body-1/70">
                {{ $t('Nothing needs your attention right now. You are all caught up.') }}
            </p>
        </section>

        <!-- Secondary stats row (demoted below the hero). -->
        <section class="grid grid-cols-2 md:grid-cols-3 gap-3">

            <button
                class="min-w-0 bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">{{ $t('This month expenses') }}</p>
                <p class="text-sm sm:text-lg font-bold text-body mt-1 break-words">
                    <MoneyPresenter :value="expenses" />
                </p>
            </button>

            <button
                class="min-w-0 bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">Ingresos del mes</p>
                <p class="text-sm sm:text-lg font-bold mt-1 break-words text-success">
                    <MoneyPresenter :value="income" />
                </p>
            </button>

            <button
                class="col-span-2 md:col-span-1 min-w-0 bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/budgets')"
            >
                <p class="text-xs text-body-1/50 uppercase tracking-wide font-medium">Disponible del presupuesto</p>
                <template v-if="budgetAssigned">
                    <p class="text-sm sm:text-lg font-bold mt-1 break-words" :class="currentBudget.total - currentBudget.spending < 0 ? 'text-error' : 'text-body'"><MoneyPresenter :value="currentBudget.total - currentBudget.spending" /></p>
                    <p class="text-xs text-body-1/70 mt-2">{{ spentPercentage }}%
                        <span class="text-xs font-normal text-body-1/50">{{ $t('spent') }}</span>
                    </p>
                    <div class="mt-2">
                        <BudgetProgress
                            class="h-1.5 rounded-full"
                            :goal="currentBudget.total"
                            :current="currentBudget.spending"
                            :progress-class="['bg-primary', 'bg-base-lvl-1']"
                            :show-labels="false"
                        />
                    </div>
                </template>
                <template v-else-if="hasBudget">
                    <p class="text-sm font-semibold text-body mt-1">{{ $t('Nothing assigned this month') }}</p>
                    <p class="text-xs text-primary mt-1">{{ $t('Assign budget') }} →</p>
                </template>
                <template v-else>
                    <p class="text-sm font-semibold text-body mt-1">{{ $t('No budget set') }}</p>
                    <p class="text-xs text-primary mt-1">{{ $t('Set your first budget') }} →</p>
                </template>
            </button>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <TodayAgendaWidget :events="agendaEvents" :date="agendaDate" />
            <div v-if="todayItems?.length" class="min-w-0">
                <DueTodayWidget :items="todayItems.slice(0, 3)" />
            </div>
            <div v-if="isMealsEnabled" class="min-w-0 rounded-xl border border-base bg-base-lvl-3 p-4" >
                <MealWidget :meals="meals?.data ?? []" :compact="true" />
            </div>
        </section>
    </div>
</template>
