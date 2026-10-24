<script setup lang="ts">
import { computed, h } from "vue";
import { useI18n } from "vue-i18n";
import { router } from "@inertiajs/vue3";
import { ElNotification } from "element-plus";
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
//
// budgetTotal only carries *assigned* amounts: `total` is everything assigned
// this month; its `spending`/`savings` split it by target type. It has NO
// actual-spending figure. The real "spent" number is the month's expense
// total, passed in as `props.expenses` (transactionTotal.total_amount).
const currentBudget = computed(() => ({
    total: Number(props.budgetTotal?.at(-1)?.total ?? 0),
}));

// Actual money spent this month (expense transactions), not an assignment
// figure. The previous card compared `total` vs `spending`; but since
// total = spending + savings, `total - spending` is just the net savings
// assignment, which flipped the card to "Over budget" whenever a savings
// category was drawn down (negative assignment) — even with nothing overspent.
const spent = computed(() => {
    const value = Number(props.expenses ?? 0);
    return Number.isFinite(value) ? Math.abs(value) : 0;
});

// What's left of the month's budget after actual spending.
const available = computed(() => currentBudget.value.total - spent.value);

// A budget "exists" if the team has it configured (categories/targets) — even
// when nothing is assigned THIS month yet. The month's assignment total is a
// separate thing (budgetAssigned below); conflating them made a fully set-up
// budget read as "No budget set" the moment a new month rolled over.
const budgetAssigned = computed(() => Number.isFinite(currentBudget.value.total) && currentBudget.value.total > 0);
const hasBudget = computed(() => Boolean(props.budgetConfigured) || budgetAssigned.value);

const spentPercentage = computed(() => {
    if (!budgetAssigned.value) return 0;
    return Math.round((spent.value / currentBudget.value.total) * 100);
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

// Drives the card accent: red when something is overdue, amber when only
// upcoming items or drafts are pending, green when everything is clear.
const attentionTone = computed(() => {
    if (hasUrgent.value) return "urgent";
    if (hasAttention.value) return "pending";
    return "clear";
});

const isOverdue = (payment: any): boolean => (daysFromToday(payment.date) ?? 0) < 0;

// "19 oct". Built from the date parts so a "YYYY-MM-DD" string isn't shifted
// a day back by UTC parsing.
const { locale, t } = useI18n();
const shortDate = (dateStr?: string | null): string => {
    const [year, month, day] = String(dateStr ?? "").slice(0, 10).split("-").map(Number);
    if (!year || !month || !day) return "";
    return new Date(year, month - 1, day)
        .toLocaleDateString(locale.value, { day: "numeric", month: "short" })
        .replace(".", "");
};

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

// Descartar un next-payment por un mes ("ya lo pagué en otra cuenta"). No crea
// transacción: guarda (id, mes) en next_payment_dismissals y el service lo oculta.
const dismiss = (payment: any) => {
    const period = String(payment.due_date ?? payment.date ?? '').slice(0, 7);
    if (!period) return;
    const payload = { payment_id: payment.id, period };
    router.post('/api/next-payments/dismiss', payload, {
        preserveScroll: true,
        onSuccess: () => {
            const notice = ElNotification({
                type: "success",
                title: t("Payment dismissed for this month"),
                message: h("button", {
                    type: "button",
                    class: "font-semibold underline",
                    onClick: () => {
                        notice.close();
                        router.delete('/api/next-payments/dismiss', { data: payload, preserveScroll: true });
                    },
                }, t("Undo")),
                duration: 6000,
            });
        },
    });
};

</script>

<template>
    <div class="space-y-4">
        <!-- Hero: what needs your attention today. Prominent block at the very
             top so overdue/upcoming items and drafts to review read as the
             primary signal; the rest of the dashboard is demoted below it. -->
        <section class="attention-card" :class="`is-${attentionTone}`" aria-labelledby="attention-title">
            <header class="attention-head">
                <span class="attention-dot" aria-hidden="true" />
                <h2 id="attention-title" class="attention-title">{{ $t('What needs your attention today') }}</h2>
                <span v-if="overduePayments.length" class="attention-count">
                    {{ $t('{count} overdue', { count: overduePayments.length }) }}
                </span>
                <span v-else-if="dueSoonPayments.length" class="attention-count">
                    {{ $t('{count} due soon', { count: dueSoonPayments.length }) }}
                </span>
            </header>

            <div
                v-for="payment in priorityPayments"
                :key="payment.id"
                class="attention-item"
                :class="isOverdue(payment) ? 'is-overdue' : 'is-upcoming'"
            >
                <div class="attention-line">
                    <p class="attention-item-title truncate">{{ payment.description || payment.title }}</p>
                    <p class="attention-amount"><MoneyPresenter :value="payment.total" /></p>
                </div>
                <div class="attention-line">
                    <p class="attention-item-sub">
                        <b>{{ isOverdue(payment) ? $t('Past due') : $t('Upcoming') }}</b> · {{ shortDate(payment.date) }}
                    </p>
                    <div class="attention-actions">
                        <button type="button" class="attention-pay" @click="handlePay(payment)">
                            {{ $t('Record payment') }}
                        </button>
                        <!-- Descartar este mes (ya pagado en otra cuenta): oculta sin crear transacción -->
                        <button
                            type="button"
                            class="attention-dismiss"
                            :title="$t('Dismiss — already paid elsewhere')"
                            :aria-label="$t('Dismiss — already paid elsewhere')"
                            @click="dismiss(payment)"
                        >
                            {{ $t('Already paid') }}
                        </button>
                    </div>
                </div>
            </div>

            <button
                v-if="overdueReminders.length"
                type="button"
                class="attention-strip"
                @click="router.visit('/housing/occurrence?overdue=1')"
            >
                <i class="fa fa-bell attention-strip-icon" aria-hidden="true" />
                <b>{{ overdueReminders.length }} {{ $t(overdueReminders.length === 1 ? 'overdue reminder' : 'overdue reminders') }}</b>
                <span class="attention-strip-link">{{ $t('Review') }} →</span>
            </button>

            <button
                v-if="draftsCount"
                type="button"
                class="attention-strip is-neutral"
                @click="router.visit('/inbox')"
            >
                <i class="fa fa-receipt attention-strip-icon" aria-hidden="true" />
                <b>{{ draftsCount }} {{ $t(draftsCount === 1 ? 'transaction to review' : 'transactions to review') }}</b>
                <span class="attention-strip-link">{{ $t('Review') }} →</span>
            </button>

            <p v-if="!hasAttention" class="attention-empty">
                <i class="fa fa-circle-check" aria-hidden="true" />
                {{ $t('Nothing needs your attention right now. You are all caught up.') }}
            </p>

            <footer v-if="nextPayments?.length" class="attention-foot">
                <button type="button" @click="router.visit('/finance/next-payments')">
                    {{ $t('See all {count}', { count: nextPayments.length }) }} →
                </button>
            </footer>
        </section>

        <!-- Secondary stats row (demoted below the hero). -->
        <section class="grid grid-cols-2 md:grid-cols-3 gap-3">

            <button
                class="min-w-0 bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/70 dark:text-body-1/80 uppercase tracking-wide font-medium">{{ $t('This month expenses') }}</p>
                <p class="text-sm sm:text-lg font-bold text-body mt-1 break-words">
                    <MoneyPresenter :value="expenses" />
                </p>
            </button>

            <button
                class="min-w-0 bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/finance/transactions')"
            >
                <p class="text-xs text-body-1/70 dark:text-body-1/80 uppercase tracking-wide font-medium">{{ $t('This month income') }}</p>
                <p class="text-sm sm:text-lg font-bold mt-1 break-words text-success">
                    <MoneyPresenter :value="income" />
                </p>
            </button>

            <button
                class="col-span-2 md:col-span-1 min-w-0 bg-base-lvl-3 rounded-lg p-4 text-left border border-base hover:border-primary/30 transition cursor-pointer"
                @click="router.visit('/budgets')"
            >
                <p class="text-xs text-body-1/70 dark:text-body-1/80 uppercase tracking-wide font-medium">{{ available < 0 ? $t('Over budget') : $t('Budget available') }}</p>
                <template v-if="budgetAssigned">
                    <p class="text-sm sm:text-lg font-bold mt-1 break-words" :class="available < 0 ? 'text-error' : 'text-body'"><MoneyPresenter :value="available" /></p>
                    <p class="text-xs text-body-1/70 mt-2">{{ spentPercentage }}%
                        <span class="text-xs font-normal text-body-1/70 dark:text-body-1/80">{{ $t('spent') }}</span>
                        <template v-if="spent > currentBudget.total">
                            <span class="text-xs font-normal text-body-1/70 dark:text-body-1/80"> · </span>
                            <span class="text-xs font-medium text-error"><MoneyPresenter :value="spent - currentBudget.total" /> {{ $t('over') }}</span>
                        </template>
                    </p>
                    <div class="mt-2">
                        <BudgetProgress
                            class="h-1.5 rounded-full"
                            :goal="currentBudget.total"
                            :current="spent"
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

<style scoped>
/* Tokens from the "Qué necesita tu atención hoy" spec (light / dark). */
.attention-card {
    --c-card: #ffffff;
    --c-line: #e6e8ec;
    --c-ink: #111827;
    --c-mut: #6a707a;
    --c-strip: #fbfbfc;
    --c-hover: rgba(0, 0, 0, 0.015);
    --c-shadow: 0 1px 2px rgba(16, 24, 40, 0.05), 0 1px 3px rgba(16, 24, 40, 0.04);
    --c-btn: #be123c;
    --c-btn-hover: #a50f34;
    --c-urgent: #be123c;
    --c-urgent-bar: #dc2626;
    --c-urgent-chip: #fdecef;
    --c-pending: #b45309;
    --c-pending-chip: #fef3e2;
    --c-clear: #16a34a;
    --c-clear-chip: #e7f6ec;

    background: var(--c-card);
    border: 1px solid var(--c-line);
    border-radius: 14px;
    box-shadow: var(--c-shadow);
    overflow: hidden;
    color: var(--c-ink);
}

/* Whole selector inside :global() — `:global(.dark) .attention-card` compiles to just `.dark`. */
:global(.dark .attention-card) {
    --c-card: #191e27;
    --c-line: #242b38;
    --c-ink: #e7eaf0;
    --c-mut: #9ca3af;
    --c-strip: #1b2028;
    --c-hover: rgba(255, 255, 255, 0.02);
    --c-shadow: none;
    --c-btn: #e11d48;
    --c-btn-hover: #c4123e;
    --c-urgent: #f2617a;
    --c-urgent-bar: #f2617a;
    --c-urgent-chip: rgba(242, 97, 122, 0.16);
    --c-pending: #e7b45a;
    --c-pending-chip: rgba(231, 180, 90, 0.16);
    --c-clear: #56c08a;
    --c-clear-chip: rgba(86, 192, 138, 0.16);
}

.attention-card.is-urgent { --c-state: var(--c-urgent); --c-bar: var(--c-urgent-bar); --c-chip: var(--c-urgent-chip); }
.attention-card.is-pending { --c-state: var(--c-pending); --c-bar: var(--c-pending); --c-chip: var(--c-pending-chip); }
.attention-card.is-clear { --c-state: var(--c-clear); --c-bar: var(--c-clear); --c-chip: var(--c-clear-chip); }

.attention-head {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 13px 16px 11px;
    border-bottom: 1px solid var(--c-line);
}
.attention-dot {
    flex: 0 0 auto;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--c-bar);
    box-shadow: 0 0 0 4px var(--c-chip);
}
.attention-title {
    margin: 0;
    font-size: 14.5px;
    font-weight: 750;
}
.attention-count {
    margin-left: auto;
    flex: 0 0 auto;
    font-size: 11px;
    font-weight: 800;
    color: var(--c-state);
    background: var(--c-chip);
    padding: 3px 9px;
    border-radius: 20px;
}

.attention-item {
    --c-row-state: var(--c-pending);
    --c-row-bar: var(--c-pending);
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--c-line);
}
.attention-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-width: 0;
}
.attention-item.is-overdue {
    --c-row-state: var(--c-urgent);
    --c-row-bar: var(--c-urgent-bar);
}
.attention-item:hover { background: var(--c-hover); }
.attention-item::before {
    content: "";
    position: absolute;
    left: 0;
    top: 10px;
    bottom: 10px;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: var(--c-row-bar);
}
.attention-item-title {
    font-size: 13.5px;
    font-weight: 600;
}
.attention-item-sub {
    margin-top: 1px;
    font-size: 11.5px;
    color: var(--c-mut);
}
.attention-item-sub b {
    color: var(--c-row-state);
    font-weight: 700;
}
.attention-amount {
    flex: 0 0 auto;
    text-align: right;
    font-size: 14.5px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}
.attention-actions {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
}
.attention-pay {
    display: inline-block;
    font-size: 11.5px;
    font-weight: 700;
    color: #ffffff;
    background: var(--c-btn);
    padding: 3px 9px;
    border-radius: 7px;
}
.attention-pay:hover { background: var(--c-btn-hover); }
.attention-dismiss {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--c-mut);
    padding: 3px 6px;
    border-radius: 7px;
}
.attention-dismiss:hover {
    color: var(--c-ink);
    text-decoration: underline;
}

/* Touch: nothing depends on hover and every control is at least 44×44. */
@media (max-width: 480px) {
    .attention-pay,
    .attention-dismiss {
        min-height: 44px;
        min-width: 44px;
        padding: 0 12px;
    }
}

.attention-strip {
    --c-strip-state: var(--c-urgent);
    display: flex;
    width: 100%;
    align-items: center;
    gap: 9px;
    padding: 11px 16px;
    background: var(--c-strip);
    border-bottom: 1px solid var(--c-line);
    font-size: 12.8px;
    text-align: left;
}
.attention-strip.is-neutral { --c-strip-state: var(--c-mut); }
@media (max-width: 480px) {
    .attention-strip { min-height: 44px; }
}
.attention-strip b { font-weight: 700; }
.attention-strip-icon { color: var(--c-strip-state); }
.attention-strip-link {
    margin-left: auto;
    flex: 0 0 auto;
    font-size: 12px;
    font-weight: 700;
    color: var(--c-strip-state);
}
.attention-strip:hover .attention-strip-link { text-decoration: underline; }

.attention-empty {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 14px 16px;
    font-size: 13px;
    color: var(--c-mut);
}
.attention-empty i { color: var(--c-clear); }

.attention-foot {
    padding: 10px 16px;
    text-align: center;
}
.attention-foot button {
    font-size: 12.3px;
    font-weight: 600;
    color: var(--c-mut);
}
.attention-foot button:hover {
    color: var(--c-state);
    text-decoration: underline;
}

.attention-card button:focus-visible {
    outline: 2px solid var(--c-state);
    outline-offset: 2px;
    border-radius: 4px;
}
</style>
