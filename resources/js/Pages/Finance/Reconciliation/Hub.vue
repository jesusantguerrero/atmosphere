<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";

import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import MoneyPresenter from "@/Components/molecules/MoneyPresenter.vue";
import { formatMoney } from "@/utils";

interface AccountRow {
    id: number;
    name: string;
    type?: string | null;
    balance: number;
    currency_code?: string;
    last_id?: number | null;
    last_date?: string | null;
    last_status?: string | null;
    last_difference?: number | null;
    days_since?: number | null;
    unreconciled_count: number;
}

const props = withDefaults(defineProps<{
    accounts: AccountRow[];
    sectionTitle?: string;
}>(), { accounts: () => [] });

const { t } = useI18n();

// A row "needs attention" when it was never reconciled, is overdue (> ~1
// statement cycle since last), or has an open reconciliation still pending.
const OVERDUE_DAYS = 35;

type Status = { key: string; label: string; cls: string; dot: string };

const statusOf = (a: AccountRow): Status => {
    if (a.last_status === "pending")
        return { key: "pending", label: "Pending", cls: "text-amber-500", dot: "bg-amber-500" };
    if (a.last_date == null)
        return { key: "never", label: "Never reconciled", cls: "text-error", dot: "bg-error" };
    if ((a.days_since ?? 0) > OVERDUE_DAYS)
        return { key: "overdue", label: "Overdue", cls: "text-amber-500", dot: "bg-amber-500" };
    return { key: "ok", label: "Up to date", cls: "text-success", dot: "bg-success" };
};

const needsAttention = (a: AccountRow) => statusOf(a).key !== "ok";
const attentionCount = computed(() => props.accounts.filter(needsAttention).length);

// Human "time since last reconciled". Days under ~6 weeks read as days,
// beyond that as months — a card 8 months behind shouldn't say "243 days".
const lastLabel = (a: AccountRow): string => {
    if (a.last_date == null) return t("Never reconciled");
    const d = a.days_since ?? 0;
    if (d <= 0) return t("Reconciled today");
    if (d < 45) return t("{n} days ago", { n: d });
    return t("~{n} months ago", { n: Math.round(d / 30) });
};

const typeLabels: Record<string, string> = {
    cash: "Cash",
    bank: "Bank",
    cash_on_hand: "Cash on Hand",
    savings: "Savings",
    credit_card: "Credit Card",
};

// Pending reconciliation → resume it (Show). Otherwise open the account's
// reconciliation home to start a new one.
const goReconcile = (a: AccountRow) => {
    if (a.last_status === "pending" && a.last_id) {
        router.visit(`/finance/reconciliation/${a.last_id}`);
    } else {
        router.visit(`/finance/accounts/${a.id}/reconciliations`);
    }
};

const ctaLabel = (a: AccountRow) => (a.last_status === "pending" ? "Continue" : "Reconcile");
</script>

<template>
    <AppLayout :title="$t('Reconciliation')" @back="router.visit('/finance')" :show-back-button="true">
        <template #header>
            <FinanceSectionNav />
        </template>

        <main class="px-5 mx-auto mt-8 mb-20 max-w-screen-md sm:px-6 lg:px-8">
            <header class="mb-4">
                <h1 class="text-lg font-bold text-body">{{ $t('Reconciliation') }}</h1>
                <p class="text-sm text-body-1/60 mt-0.5">
                    <template v-if="attentionCount">
                        {{ attentionCount }}
                        {{ attentionCount === 1 ? $t('account needs attention') : $t('accounts need attention') }}
                        · {{ $t('most behind first') }}
                    </template>
                    <template v-else>{{ $t('Everything is reconciled. Nice.') }}</template>
                </p>
            </header>

            <section class="bg-base-lvl-3 rounded-lg border border-base divide-y divide-base overflow-hidden">
                <article
                    v-for="a in accounts"
                    :key="a.id"
                    class="flex items-center gap-4 px-4 py-3 hover:bg-base-lvl-2 transition cursor-pointer"
                    @click="goReconcile(a)"
                >
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="statusOf(a).dot" :title="$t(statusOf(a).label)" />

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-sm font-semibold text-body truncate">{{ a.name }}</span>
                            <span class="text-[11px] text-body-1/40 flex-shrink-0">{{ $t(typeLabels[a.type ?? ''] ?? (a.type ?? '')) }}</span>
                        </div>
                        <div class="text-xs mt-0.5 flex items-center gap-2 flex-wrap">
                            <span :class="statusOf(a).cls" class="font-semibold">{{ $t(statusOf(a).label) }}</span>
                            <span class="text-body-1/50">· {{ lastLabel(a) }}</span>
                            <span v-if="a.last_status === 'pending' && a.last_difference" class="text-amber-500">
                                · {{ $t('difference') }} {{ formatMoney(Math.abs(a.last_difference), a.currency_code) }}
                            </span>
                            <span v-if="a.unreconciled_count" class="text-body-1/50">
                                · {{ a.unreconciled_count }} {{ $t('unreconciled') }}
                            </span>
                        </div>
                    </div>

                    <div class="text-right flex-shrink-0 hidden sm:block">
                        <div class="text-sm font-semibold tabular-nums" :class="a.balance >= 0 ? 'text-body' : 'text-error'">
                            <MoneyPresenter :value="a.balance" />
                        </div>
                        <div class="text-[11px] text-body-1/40">{{ $t('balance') }}</div>
                    </div>

                    <button
                        type="button"
                        class="flex-shrink-0 text-xs font-bold px-3 py-2 rounded-lg transition"
                        :class="needsAttention(a)
                            ? 'bg-primary text-white hover:brightness-105'
                            : 'bg-base-lvl-2 text-body-1/70 hover:text-body'"
                        @click.stop="goReconcile(a)"
                    >
                        {{ $t(ctaLabel(a)) }}
                    </button>
                </article>

                <div v-if="!accounts.length" class="px-5 py-10 text-center text-sm text-body-1/60">
                    {{ $t('No accounts to reconcile yet.') }}
                </div>
            </section>
        </main>
    </AppLayout>
</template>
