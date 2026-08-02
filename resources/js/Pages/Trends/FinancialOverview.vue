<script setup lang="ts">
import InfoHint from "@/Components/atoms/InfoHint.vue";
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { NPopover } from "naive-ui";

import AppLayout from "@/Components/templates/AppLayout.vue";
import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import { useTrendOptions } from "./Partials/trendOptions";
const trendOptions = useTrendOptions();
import { formatMoney } from "@/utils";
import { useI18n } from "vue-i18n";

interface AccountEntry {
    id: number; name: string; bank_code: string | null;
    currency_code: string; balance: number;
    base_balance: number | null; quote_balance: number | null;
    base_in_quote: number | null; is_multi_currency: boolean;
}
interface AccountGroup {
    group: string; accounts: AccountEntry[];
    subtotals: { base: number; quote: number; base_in_quote: number };
}
interface LinkedAccount { id: number; name: string; bank_code: string | null; balance_in_quote: number; }
interface LinkedCategory { id: number; name: string; team_name: string; available_in_quote: number; }
interface Goal {
    id: string; name: string; target_amount: number; current_balance: number;
    linked_accounts: LinkedAccount[];
    linked_categories: LinkedCategory[];
}
interface AvailableCategoryEntry { id: number; name: string; team_id: number; available_in_quote: number; }
interface AvailableCategoryGroup { team_name: string; categories: AvailableCategoryEntry[]; }
interface BankBreakdownEntry {
    bank_code: string | null; label: string; total_in_quote: number; account_count: number;
}
interface Totals {
    total_base: number; total_quote: number; base_in_quote: number;
    net_worth: number; base_currency: string; quote_currency: string;
}

const props = withDefaults(defineProps<{
    user: Record<string, any>;
    accounts: AccountGroup[];
    pinnedGoals: Goal[];
    availableGoals: Goal[];
    pinnedGoalIds: string[];
    goalAccountLinks: Record<string, number[]>;
    goalCategoryLinks: Record<string, number[]>;
    availableCategories: AvailableCategoryGroup[];
    bankBreakdown: BankBreakdownEntry[];
    exchangeRate: number;
    baseCurrency: string;
    quoteCurrency: string;
    totals: Totals;
}>(), {
    accounts: () => [], pinnedGoals: () => [], availableGoals: () => [],
    pinnedGoalIds: () => [], goalAccountLinks: () => ({}), goalCategoryLinks: () => ({}),
    availableCategories: () => [], bankBreakdown: () => [],
    exchangeRate: 59.8, baseCurrency: 'USD', quoteCurrency: 'DOP',
});

const localExchangeRate = ref(props.exchangeRate);
const isSavingRate = ref(false);
const saveSuccess = ref(false);
const showGoalPicker = ref(false);
const localPinnedIds = ref<string[]>([...props.pinnedGoalIds]);
const { t } = useI18n();

// Only surface the FX / exchange-rate widget when the team actually holds
// value in a currency other than the space's primary (quote) currency.
const hasForeignCurrency = computed<boolean>(() =>
    props.accounts.some(g => g.accounts.some(a =>
        a.currency_code !== props.quoteCurrency || a.is_multi_currency || (a.base_balance ?? 0) !== 0
    )) || (props.totals?.total_base ?? 0) !== 0
);

const groupLabel = (group: string) => {
    const labels: Record<string, string> = {
        bank: t('Bank'), savings: t('Savings'), cash: t('Cash'), cash_on_hand: t('Cash on Hand'),
        credit_card: t('Credit Card'), money_market: t('Money Market'), other: t('Other'),
    };
    return labels[group] ?? group;
};

const goalsTotal = computed(() =>
    props.pinnedGoals.reduce((sum, g) => sum + g.current_balance, 0)
);

const flatAccounts = computed(() =>
    props.accounts.flatMap(g => g.accounts.map(a => ({ id: a.id, name: a.name, group: groupLabel(g.group) })))
);

const editingGoalLinkId = ref<string | null>(null);

function isAccountLinkedToGoal(goal: Goal, accountId: number): boolean {
    return goal.linked_accounts.some(a => a.id === accountId);
}

async function toggleGoalAccountLink(goal: Goal, accountId: number) {
    const current = goal.linked_accounts.map(a => a.id);
    const next = { ...props.goalAccountLinks };
    const updated = current.includes(accountId)
        ? current.filter(id => id !== accountId)
        : [...current, accountId];

    if (updated.length) {
        next[goal.id] = updated;
    } else {
        delete next[goal.id];
    }

    await axios.post('/trends/financial-overview/goal-account-links', { links: next });
    router.reload();
}

function isCategoryLinkedToGoal(goal: Goal, categoryId: number): boolean {
    return goal.linked_categories.some(c => c.id === categoryId);
}

async function toggleGoalCategoryLink(goal: Goal, categoryId: number) {
    const current = goal.linked_categories.map(c => c.id);
    const next = { ...props.goalCategoryLinks };
    const updated = current.includes(categoryId)
        ? current.filter(id => id !== categoryId)
        : [...current, categoryId];

    if (updated.length) {
        next[goal.id] = updated;
    } else {
        delete next[goal.id];
    }

    await axios.post('/trends/financial-overview/goal-category-links', { links: next });
    router.reload();
}

const isGoalPinned = (id: string) => localPinnedIds.value.includes(id);

function toggleGoalPin(id: string) {
    if (isGoalPinned(id)) {
        localPinnedIds.value = localPinnedIds.value.filter(i => i !== id);
    } else {
        localPinnedIds.value.push(id);
    }
}

async function savePinnedGoals() {
    await axios.post('/trends/financial-overview/pinned-goals', {
        pinned_goals: localPinnedIds.value,
    });
    showGoalPicker.value = false;
    router.reload();
}

function removeGoal(id: string) {
    localPinnedIds.value = localPinnedIds.value.filter(i => i !== id);
    axios.post('/trends/financial-overview/pinned-goals', {
        pinned_goals: localPinnedIds.value,
    }).then(() => router.reload());
}

const editingBankAccountId = ref<number | null>(null);
const bankCodeDraft = ref<string>("");

const knownBankCodes = computed<string[]>(() => {
    const set = new Set<string>();
    for (const group of props.accounts) {
        for (const acc of group.accounts) {
            if (acc.bank_code) {
                set.add(acc.bank_code);
            }
        }
    }
    return Array.from(set).sort();
});

function openBankEditor(account: AccountEntry): void {
    editingBankAccountId.value = account.id;
    bankCodeDraft.value = account.bank_code ?? "";
}

function closeBankEditor(): void {
    editingBankAccountId.value = null;
    bankCodeDraft.value = "";
}

function saveBankCode(account: AccountEntry): void {
    const value = bankCodeDraft.value.trim();
    router.patch(
        route("finance.accounts.bank-code", { account: account.id }),
        { bank_code: value === "" ? null : value },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeBankEditor();
                router.reload();
            },
        }
    );
}

async function saveExchangeRate() {
    isSavingRate.value = true;
    saveSuccess.value = false;
    try {
        await axios.post('/trends/financial-overview/settings', {
            exchange_rate: localExchangeRate.value,
            base_currency: props.baseCurrency,
            quote_currency: props.quoteCurrency,
        });
        saveSuccess.value = true;
        setTimeout(() => { saveSuccess.value = false; window.location.reload(); }, 600);
    } finally {
        isSavingRate.value = false;
    }
}
</script>

<template>
    <AppLayout :title="$t('Financial Overview')">
        <template #header>
            <TrendSectionNav :sections="trendOptions" />
        </template>

        <TrendTemplate :title="$t('Finance')" :hide-panel="true">
            <div class="space-y-5 mt-5">

                <!-- Net Worth bar with inline exchange rate -->
                <section class="bg-primary text-white rounded-lg px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-xs uppercase tracking-wide opacity-70 inline-flex items-center gap-1">{{ $t('Net worth') }} <InfoHint :title="$t('Net worth')" :body="$t('net_worth_hint')" /></p>
                            <p class="text-2xl font-bold break-all leading-tight">
                                {{ formatMoney(totals.net_worth, quoteCurrency) }}
                            </p>
                            <p class="text-[11px] opacity-70">{{ $t('Includes accounts in {currency} (converted)', { currency: baseCurrency }) }}</p>
                        </div>
                        <div class="hidden md:flex items-center gap-4 text-sm opacity-80">
                            <span>{{ formatMoney(totals.total_quote, quoteCurrency) }}</span>
                            <span class="opacity-60">+</span>
                            <span>{{ formatMoney(totals.total_base, baseCurrency) }}
                                <span class="text-xs opacity-60">(≈ {{ formatMoney(totals.base_in_quote, quoteCurrency) }})</span>
                            </span>
                        </div>
                    </div>
                    <div v-if="hasForeignCurrency" class="flex items-center gap-2">
                        <span class="text-xs opacity-60">1 {{ baseCurrency }} =</span>
                        <input
                            v-model.number="localExchangeRate"
                            type="number" min="0" step="0.01"
                            class="w-20 px-2 py-1 text-sm rounded bg-base-lvl-3/20 text-white placeholder-white/50 border-0 focus:outline-none focus:ring-1 focus:ring-white/50"
                        />
                        <span class="text-xs opacity-60">{{ quoteCurrency }}</span>
                        <button
                            :disabled="isSavingRate || localExchangeRate === exchangeRate"
                            class="px-3 py-1 text-xs font-medium rounded bg-base-lvl-3/20 hover:bg-base-lvl-3/30 disabled:opacity-30 transition"
                            @click="saveExchangeRate"
                        >
                            {{ saveSuccess ? $t('Saved!') : $t('Update') }}
                        </button>
                    </div>
                </section>

                <!-- Side by side: Goals | Accounts -->
                <section class="flex flex-col lg:flex-row gap-4">

                    <!-- LEFT: Financial Goals (curated) -->
                    <div class="w-full lg:w-5/12">
                        <div class="bg-base-lvl-3 border border-base rounded-lg overflow-hidden h-full">
                            <div class="px-5 py-3 border-b border-base flex items-center justify-between">
                                <h2 class="font-bold text-body text-sm">{{ $t('Financial Goals') }}</h2>
                                <LogerButton variant="inverse" class="text-xs" @click="showGoalPicker = !showGoalPicker">
                                    <IMdiPlus class="mr-1" v-if="!showGoalPicker" /> {{ showGoalPicker ? $t('Done') : $t('Add') }}
                                </LogerButton>
                            </div>

                            <!-- Goal picker -->
                            <div v-if="showGoalPicker" class="px-5 py-3 border-b border-base bg-base-lvl-1 space-y-2">
                                <p class="text-xs text-body-1/50 mb-2">{{ $t('Select goals to show in this view:') }}</p>
                                <label
                                    v-for="goal in availableGoals"
                                    :key="goal.id"
                                    class="flex items-center gap-2 py-1 cursor-pointer text-sm text-body hover:text-primary"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="isGoalPinned(goal.id)"
                                        @change="toggleGoalPin(goal.id)"
                                        class="rounded border-base text-primary focus:ring-primary"
                                    />
                                    {{ goal.name }}
                                    <span v-if="goal.target_amount" class="text-xs text-body-1/40 ml-auto">
                                        {{ formatMoney(goal.target_amount, quoteCurrency) }}
                                    </span>
                                </label>
                                <LogerButton class="mt-2 w-full justify-center text-xs" @click="savePinnedGoals">
                                    {{ $t('Save selection') }}
                                </LogerButton>
                            </div>

                            <!-- Pinned goals table -->
                            <table v-if="pinnedGoals.length" class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs uppercase text-body-1/50 bg-base-lvl-2">
                                        <th class="text-left px-5 py-2 font-medium">{{ $t('Goal') }}</th>
                                        <th class="text-right px-5 py-2 font-medium">{{ $t('Balance / Target') }}</th>
                                        <th class="text-right px-5 py-2 font-medium">{{ $t('Diff') }}</th>
                                        <th class="w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="goal in pinnedGoals" :key="goal.id" class="border-b border-base">
                                        <td class="px-5 py-3 text-body align-top">
                                            <div class="font-medium">{{ goal.name }}</div>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                                <span
                                                    v-for="acc in goal.linked_accounts"
                                                    :key="acc.id"
                                                    class="inline-flex items-center gap-1 text-[11px] leading-none px-2 py-1 rounded-full bg-primary/10 text-primary max-w-[200px]"
                                                >
                                                    <span class="truncate">
                                                        {{ acc.name }}<span v-if="acc.bank_code" class="opacity-60"> · {{ acc.bank_code }}</span>
                                                    </span>
                                                    <button class="opacity-50 hover:opacity-100 hover:text-red-400 transition flex-shrink-0" @click="toggleGoalAccountLink(goal, acc.id)" title="Unlink">
                                                        <IMdiClose class="text-[10px]" />
                                                    </button>
                                                </span>
                                                <span
                                                    v-for="cat in goal.linked_categories"
                                                    :key="`cat-${cat.id}`"
                                                    class="inline-flex items-center gap-1 text-[11px] leading-none px-2 py-1 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 max-w-[200px]"
                                                >
                                                    <IMdiFolder class="text-[10px] flex-shrink-0" />
                                                    <span class="truncate">{{ cat.name }}</span>
                                                    <span v-if="cat.team_name" class="opacity-60 truncate"> · {{ cat.team_name }}</span>
                                                    <button class="opacity-50 hover:opacity-100 hover:text-red-400 transition flex-shrink-0" @click="toggleGoalCategoryLink(goal, cat.id)" title="Unlink">
                                                        <IMdiClose class="text-[10px]" />
                                                    </button>
                                                </span>
                                                <button
                                                    class="inline-flex items-center gap-0.5 text-[11px] leading-none px-2 py-1 rounded-full border border-dashed border-base text-body-1/50 hover:text-primary hover:border-primary transition"
                                                    @click="editingGoalLinkId = editingGoalLinkId === goal.id ? null : goal.id"
                                                >
                                                    <IMdiPlus class="text-[10px]" />
                                                    <span>{{ editingGoalLinkId === goal.id ? $t('Done') : (goal.linked_accounts.length || goal.linked_categories.length ? $t('Add') : $t('Link')) }}</span>
                                                </button>
                                            </div>
                                            <div v-if="editingGoalLinkId === goal.id" class="mt-2 max-h-48 overflow-y-auto border border-base rounded bg-base-lvl-1 p-2 space-y-1">
                                                <p class="text-[10px] font-semibold uppercase tracking-wide text-body-1/40 px-1 pt-1">{{ $t('Accounts') }}</p>
                                                <label
                                                    v-for="a in flatAccounts"
                                                    :key="a.id"
                                                    class="flex items-center gap-2 text-xs text-body hover:text-primary cursor-pointer"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :checked="isAccountLinkedToGoal(goal, a.id)"
                                                        @change="toggleGoalAccountLink(goal, a.id)"
                                                        class="rounded border-base text-primary focus:ring-primary"
                                                    />
                                                    <span>{{ a.name }}</span>
                                                    <span class="text-body-1/40 ml-auto">{{ a.group }}</span>
                                                </label>
                                                <template v-if="availableCategories.length">
                                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-body-1/40 px-1 pt-2">{{ $t('Categories') }}</p>
                                                    <template v-for="group in availableCategories" :key="group.team_name">
                                                        <label
                                                            v-for="cat in group.categories"
                                                            :key="cat.id"
                                                            class="flex items-center gap-2 text-xs text-body hover:text-emerald-600 dark:hover:text-emerald-400 cursor-pointer"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                :checked="isCategoryLinkedToGoal(goal, cat.id)"
                                                                @change="toggleGoalCategoryLink(goal, cat.id)"
                                                                class="rounded border-base text-emerald-500 focus:ring-emerald-400"
                                                            />
                                                            <span>{{ cat.name }}</span>
                                                            <span class="text-body-1/40 ml-auto">{{ group.team_name }}</span>
                                                        </label>
                                                    </template>
                                                </template>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-right tabular-nums align-top">
                                            <div>{{ formatMoney(goal.current_balance, quoteCurrency) }}</div>
                                            <div class="text-xs text-body-1/50">
                                                <span v-if="goal.target_amount">/ {{ formatMoney(goal.target_amount, quoteCurrency) }}</span>
                                                <span v-else>—</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-right tabular-nums font-semibold align-top"
                                            :class="(goal.current_balance - goal.target_amount) >= 0 ? 'text-green-500' : 'text-red-400'">
                                            <span v-if="goal.target_amount">
                                                {{ formatMoney(goal.current_balance - goal.target_amount, quoteCurrency) }}
                                            </span>
                                            <span v-else class="text-body-1/40 font-normal">—</span>
                                        </td>
                                        <td class="pr-3 align-top py-3">
                                            <button @click="removeGoal(goal.id)" class="text-body-1/30 hover:text-red-400 transition" title="Remove from view">
                                                <IMdiClose class="text-xs" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-base-lvl-2 font-bold">
                                        <td class="px-5 py-2 text-body">{{ $t('Total') }}</td>
                                        <td class="px-5 py-2 text-right tabular-nums">{{ formatMoney(goalsTotal, quoteCurrency) }}</td>
                                        <td></td><td></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div v-else-if="!showGoalPicker" class="px-5 py-8 text-center text-body-1/40 text-sm">
                                {{ $t('Click') }} <strong>{{ $t('Add') }}</strong> {{ $t('to pin goals to this view.') }}
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Accounts by Bank -->
                    <div class="w-full lg:w-7/12">
                        <div class="bg-base-lvl-3 border border-base rounded-lg overflow-hidden h-full">
                            <div class="px-5 py-3 border-b border-base">
                                <h2 class="font-bold text-body text-sm">{{ $t('Accounts') }}</h2>
                            </div>

                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs uppercase text-body-1/50 bg-base-lvl-2">
                                        <th class="text-left px-5 py-2 font-medium">{{ $t('Account') }}</th>
                                        <th class="text-right px-5 py-2 font-medium">{{ baseCurrency }}</th>
                                        <th class="text-right px-5 py-2 font-medium">{{ quoteCurrency }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="group in accounts" :key="group.group">
                                        <tr class="bg-base-lvl-1">
                                            <td class="px-5 py-1.5 text-xs font-semibold uppercase tracking-wide text-body-1/60" colspan="3">
                                                {{ groupLabel(group.group) }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="account in group.accounts"
                                            :key="account.id"
                                            class="border-b border-base hover:bg-base-lvl-1/50 transition cursor-pointer"
                                            @click="router.visit(`/finance/accounts/${account.id}`)"
                                        >
                                            <td class="px-5 py-2 text-body pl-8">
                                                <span>{{ account.name }}</span>
                                                <NPopover
                                                    trigger="manual"
                                                    :show="editingBankAccountId === account.id"
                                                    placement="bottom-start"
                                                    @clickoutside="closeBankEditor"
                                                >
                                                    <template #trigger>
                                                        <button
                                                            type="button"
                                                            class="ml-1.5 text-xs"
                                                            :class="account.bank_code ? 'text-body-1/60 hover:text-primary' : 'text-body-1/40 hover:text-primary'"
                                                            @click.stop="openBankEditor(account)"
                                                        >
                                                            <span v-if="account.bank_code">· {{ account.bank_code }}</span>
                                                            <span v-else class="opacity-60">+ {{ $t('Bank') }}</span>
                                                        </button>
                                                    </template>
                                                    <div class="space-y-2 min-w-[220px]" @click.stop>
                                                        <p class="text-xs text-body-1">{{ $t('Bank') }}</p>
                                                        <LogerInput
                                                            v-model="bankCodeDraft"
                                                            placeholder="BHD / APAP / Popular"
                                                            list="known-bank-codes"
                                                            @keydown.enter="saveBankCode(account)"
                                                            @keydown.esc="closeBankEditor"
                                                        />
                                                        <datalist id="known-bank-codes">
                                                            <option v-for="code in knownBankCodes" :key="code" :value="code" />
                                                        </datalist>
                                                        <div class="flex justify-end gap-2 pt-1">
                                                            <button type="button" class="text-xs text-body-1 hover:text-body" @click="closeBankEditor">
                                                                {{ $t('Cancel') }}
                                                            </button>
                                                            <button type="button" class="text-xs font-semibold text-primary hover:text-primary/80" @click="saveBankCode(account)">
                                                                {{ $t('Save') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </NPopover>
                                            </td>
                                            <td class="px-5 py-2 text-right tabular-nums">
                                                <span v-if="account.base_balance !== null">{{ formatMoney(account.base_balance, baseCurrency) }}</span>
                                            </td>
                                            <td class="px-5 py-2 text-right tabular-nums">
                                                <template v-if="account.quote_balance !== null">
                                                    {{ formatMoney(account.quote_balance, quoteCurrency) }}
                                                </template>
                                                <template v-else-if="account.base_in_quote !== null">
                                                    <span class="text-body-1/50">{{ formatMoney(account.base_in_quote, quoteCurrency) }}</span>
                                                </template>
                                            </td>
                                        </tr>
                                        <tr class="border-b-2 border-base bg-base-lvl-1/50">
                                            <td class="px-5 py-1.5 text-xs font-semibold text-body-1/60 pl-8">{{ $t('Subtotal') }}</td>
                                            <td class="px-5 py-1.5 text-right text-xs font-semibold tabular-nums">
                                                <span v-if="group.subtotals.base">{{ formatMoney(group.subtotals.base, baseCurrency) }}</span>
                                            </td>
                                            <td class="px-5 py-1.5 text-right text-xs font-semibold tabular-nums">
                                                {{ formatMoney(group.subtotals.quote + group.subtotals.base_in_quote, quoteCurrency) }}
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-base-lvl-2 font-bold">
                                        <td class="px-5 py-3 text-body">{{ $t('Total') }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums">{{ formatMoney(totals.total_base, baseCurrency) }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums">{{ formatMoney(totals.total_quote + totals.base_in_quote, quoteCurrency) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- By Bank breakdown -->
                <section v-if="bankBreakdown.length > 1" class="bg-base-lvl-3 border border-base rounded-lg overflow-hidden">
                    <div class="px-5 py-3 border-b border-base">
                        <h2 class="font-bold text-body text-sm">{{ $t('By Bank') }}</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-body-1/50 bg-base-lvl-2">
                                <th class="text-left px-5 py-2 font-medium">{{ $t('Bank') }}</th>
                                <th class="text-right px-5 py-2 font-medium">{{ $t('Accounts') }}</th>
                                <th class="text-right px-5 py-2 font-medium">{{ quoteCurrency }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in bankBreakdown" :key="entry.label" class="border-b border-base">
                                <td class="px-5 py-2 text-body">
                                    <span v-if="entry.bank_code" class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-primary/10 text-primary">
                                        {{ entry.bank_code }}
                                    </span>
                                    <span v-else class="italic text-body-1/50">{{ $t('Unlinked') }}</span>
                                </td>
                                <td class="px-5 py-2 text-right text-body-1/60 tabular-nums">{{ entry.account_count }}</td>
                                <td class="px-5 py-2 text-right tabular-nums">{{ formatMoney(entry.total_in_quote, quoteCurrency) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-base-lvl-2 font-bold">
                                <td class="px-5 py-2 text-body">{{ $t('Net worth') }}</td>
                                <td></td>
                                <td class="px-5 py-2 text-right tabular-nums">
                                    {{ formatMoney(totals.total_quote + totals.base_in_quote, quoteCurrency) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </section>

            </div>
        </TrendTemplate>
    </AppLayout>
</template>
