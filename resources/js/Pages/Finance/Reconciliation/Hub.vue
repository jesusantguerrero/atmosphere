<script setup lang="ts">
import { computed, ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import axios from "axios";
import { format } from "date-fns";
import { useI18n } from "vue-i18n";

import AppLayout from "@/Components/templates/AppLayout.vue";
import FinanceSectionNav from "../Partials/FinanceSectionNav.vue";
import NumberHider from "@/Components/molecules/NumberHider.vue";
import AccountReconciliationForm from "../AccountReconciliationForm.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
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

const props = withDefaults(
    defineProps<{
        accounts: AccountRow[];
        sectionTitle?: string;
    }>(),
    { accounts: () => [] },
);

const { t } = useI18n();

// A row "needs attention" when it was never reconciled, is overdue (> ~1
// statement cycle since last), or has an open reconciliation still pending.
const OVERDUE_DAYS = 35;

type Status = { key: string; label: string; cls: string; dot: string };

const statusOf = (a: AccountRow): Status => {
    if (a.last_status === "pending")
        return {
            key: "pending",
            label: "Pending",
            cls: "text-amber-700 dark:text-amber-400",
            dot: "bg-amber-500",
        };
    if (a.last_date == null)
        return {
            key: "never",
            label: "Never reconciled",
            cls: "text-error",
            dot: "bg-error",
        };
    if (a.unreconciled_count > 0)
        return {
            key: "review",
            label: "Movements to review",
            cls: "text-amber-700 dark:text-amber-400",
            dot: "bg-amber-500",
        };
    if ((a.days_since ?? 0) > OVERDUE_DAYS)
        return {
            key: "overdue",
            label: "Overdue",
            cls: "text-amber-700 dark:text-amber-400",
            dot: "bg-amber-500",
        };
    return {
        key: "ok",
        label: "Up to date",
        cls: "text-success",
        dot: "bg-success",
    };
};

const needsAttention = (a: AccountRow) => statusOf(a).key !== "ok";
const attentionCount = computed(
    () => props.accounts.filter(needsAttention).length,
);
const accountGroups = computed(() =>
    [
        {
            key: "pending",
            label: "In progress",
            accounts: props.accounts.filter(
                (a) => statusOf(a).key === "pending",
            ),
        },
        {
            key: "review",
            label: "To review",
            accounts: props.accounts.filter(
                (a) => !["pending", "ok"].includes(statusOf(a).key),
            ),
        },
        {
            key: "ok",
            label: "Up to date",
            accounts: props.accounts.filter((a) => statusOf(a).key === "ok"),
        },
    ].filter((group) => group.accounts.length),
);

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

const accountToReconcile = ref<AccountRow | null>(null);
const quickAccountId = ref<number | null>(null);
const quickForm = useForm({ date: "", balance: 0 });
const quickConfirmation = ref<AccountRow | null>(null);

const cancelQuickReconciliation = () => {
    if (quickForm.processing) return;
    quickConfirmation.value = null;
    quickAccountId.value = null;
};

const confirmQuickReconciliation = () => {
    if (!quickConfirmation.value || quickForm.processing) return;
    quickForm.post(`/finance/reconciliation/accounts/${quickConfirmation.value.id}`, {
        onSuccess: () => { quickConfirmation.value = null; },
        onFinish: () => { quickAccountId.value = null; },
    });
};

const quickReconcile = async (account: AccountRow) => {
    if (quickAccountId.value !== null || quickForm.processing) return;
    quickAccountId.value = account.id;
    quickForm.clearErrors();
    quickForm.date = format(new Date(), "yyyy-MM-dd");
    try {
        const { data } = await axios.get(`/finance/accounts/${account.id}/balance-at`, {
            params: { date: quickForm.date },
        });
        quickForm.balance = Number(data.balance);
        if (!Number.isFinite(quickForm.balance)) throw new Error("Invalid account balance");
        quickConfirmation.value = account;
    } catch {
        quickAccountId.value = null;
        quickForm.setError("balance", t("Could not load the account balance. Try again."));
    }
};

const goReconcile = (a: AccountRow) => {
    if (a.last_status === "pending" && a.last_id) {
        router.visit(`/finance/reconciliation/${a.last_id}`);
    } else {
        accountToReconcile.value = a;
    }
};

const ctaLabel = (a: AccountRow) =>
    a.last_status === "pending" ? "Continue" : "Reconcile";
</script>

<template>
    <AppLayout
        :title="$t('Reconciliation')"
        @back="router.visit('/finance')"
        :show-back-button="true"
    >
        <template #header>
            <FinanceSectionNav />
        </template>

        <main class="px-5 sm:px-6 lg:px-8 mt-16 pb-36 max-w-screen-xl">
            <p v-if="quickForm.errors.balance || quickForm.errors.date" role="alert" class="text-sm text-error mb-3">
                {{ quickForm.errors.balance || quickForm.errors.date }}
            </p>
            <header class="mb-4">
                <h1 class="text-lg font-bold text-body">
                    {{ $t("Reconciliation") }}
                </h1>
                <p class="text-sm text-body-1/80 mt-1">
                    <template v-if="attentionCount">
                        {{ attentionCount }}
                        {{
                            attentionCount === 1
                                ? $t("account needs attention")
                                : $t("accounts need attention")
                        }}
                    </template>
                    <template v-else-if="accounts.length">{{
                        $t("Everything is reconciled. Nice.")
                    }}</template>
                </p>
                <p class="text-sm text-body-1/80 mt-2">
                    {{
                        $t(
                            "Compare your statement with Loger and resolve any differences.",
                        )
                    }}
                </p>
            </header>

            <section
                v-for="group in accountGroups"
                :key="group.key"
                class="mb-6"
                :aria-labelledby="`group-${group.key}`"
            >
                <h2
                    :id="`group-${group.key}`"
                    class="text-sm font-semibold text-body mb-2"
                >
                    {{ $t(group.label) }}
                    <span class="text-body-1/80"
                        >({{ group.accounts.length }})</span
                    >
                </h2>
                <div
                    class="bg-base-lvl-3 rounded-lg border border-base divide-y divide-base overflow-hidden"
                >
                    <article
                        v-for="a in group.accounts"
                        :key="a.id"
                        class="flex items-center gap-3 sm:gap-4 px-4 py-4"
                    >
                        <span
                            class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                            :class="statusOf(a).dot"
                            :title="$t(statusOf(a).label)"
                        />

                        <div class="min-w-0 flex-1">
                            <div
                                class="flex flex-col items-start gap-1 sm:flex-row sm:items-center sm:gap-2 min-w-0"
                            >
                                <a
                                    :href="
                                        a.last_status === 'pending' && a.last_id
                                            ? `/finance/reconciliation/${a.last_id}`
                                            : `/finance/accounts/${a.id}/reconciliations`
                                    "
                                    class="text-sm font-semibold text-body break-words w-full sm:w-auto hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                                    >{{ a.name }}</a
                                >
                                <span
                                    class="text-xs text-body-1/80 flex-shrink-0"
                                    >{{
                                        $t(
                                            typeLabels[a.type ?? ""] ??
                                                a.type ??
                                                "",
                                        )
                                    }}</span
                                >
                            </div>
                            <div
                                class="text-xs mt-0.5 flex items-center gap-2 flex-wrap"
                            >
                                <span
                                    :class="statusOf(a).cls"
                                    class="font-semibold"
                                    >{{ $t(statusOf(a).label) }}</span
                                >
                                <span v-if="a.last_date" class="text-body-1/80"
                                    >· {{ lastLabel(a) }}</span
                                >
                                <span
                                    v-if="
                                        a.last_status === 'pending' &&
                                        a.last_difference
                                    "
                                    class="text-amber-700 dark:text-amber-400"
                                >
                                    · {{ $t("difference") }}
                                    {{
                                        formatMoney(
                                            Math.abs(a.last_difference),
                                            a.currency_code,
                                        )
                                    }}
                                </span>
                                <span
                                    v-if="a.unreconciled_count"
                                    class="text-body-1/80"
                                >
                                    · {{ a.unreconciled_count }}
                                    {{ $t("unreconciled") }}
                                </span>
                            </div>
                            <div
                                class="sm:hidden text-sm tabular-nums text-body mt-2"
                            >
                                <span class="relative inline-block"
                                    ><NumberHider />{{
                                        formatMoney(a.balance, a.currency_code)
                                    }}</span
                                >
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0 hidden sm:block">
                            <div
                                class="text-sm font-semibold tabular-nums"
                                :class="
                                    a.balance >= 0 ? 'text-body' : 'text-error'
                                "
                            >
                                <span class="relative inline-block"
                                    ><NumberHider />{{
                                        formatMoney(a.balance, a.currency_code)
                                    }}</span
                                >
                            </div>
                            <div class="text-xs text-body-1/80">
                                {{ $t("balance") }}
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 flex-shrink-0">
                        <button
                            v-if="a.last_status !== 'pending'"
                            type="button"
                            class="text-xs font-semibold px-3 py-2 rounded-lg border border-body-1/30 text-body hover:bg-base-lvl-2 disabled:opacity-50"
                            :disabled="quickAccountId !== null || quickForm.processing"
                            @click="quickReconcile(a)"
                        >
                            {{ $t('Balance matches: reconcile') }}
                        </button>
                        <button
                            type="button"
                            class="flex-shrink-0 text-sm font-semibold px-3 py-2.5 rounded-lg transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                            :class="
                                a.last_status === 'pending'
                                    ? 'bg-primary text-white hover:brightness-105'
                                    : 'border border-body-1/30 bg-base-lvl-3 text-body hover:bg-base-lvl-2'
                            "
                            @click.stop="goReconcile(a)"
                        >
                            {{ $t(ctaLabel(a)) }}
                        </button>
                        </div>
                    </article>
                </div>
            </section>
            <div
                v-if="!accounts.length"
                class="px-5 py-10 text-center text-sm text-body-1/60"
            >
                {{ $t("No accounts to reconcile yet.") }}
            </div>
        </main>
        <ConfirmationModal
            :show="quickConfirmation !== null"
            :closeable="!quickForm.processing"
            max-width="md"
            :title="$t('Confirm reconciliation')"
            @close="cancelQuickReconciliation"
        >
            <template #content>
                <p class="font-semibold text-body">{{ quickConfirmation?.name }}</p>
                <p class="mt-2 text-sm text-body">
                    {{ $t('Does your statement match {balance} on {date}?', {
                        balance: formatMoney(quickForm.balance, quickConfirmation?.currency_code),
                        date: quickForm.date,
                    }) }}
                </p>
                <p v-if="quickForm.errors.balance || quickForm.errors.date" role="alert" class="text-sm text-error mt-2">
                    {{ quickForm.errors.balance || quickForm.errors.date }}
                </p>
            </template>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <LogerButton variant="neutral" :disabled="quickForm.processing" @click="cancelQuickReconciliation">{{ $t('Cancel') }}</LogerButton>
                    <LogerButton :disabled="quickForm.processing" @click="confirmQuickReconciliation">{{ $t('Yes, reconcile') }}</LogerButton>
                </div>
            </template>
        </ConfirmationModal>
        <AccountReconciliationForm
            v-if="accountToReconcile"
            :account="accountToReconcile"
            :is-visible="true"
            start-detailed
            @close="accountToReconcile = null"
        />
    </AppLayout>
</template>
