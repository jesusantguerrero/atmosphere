<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";

import AppLayout from "@/Components/templates/AppLayout.vue";
import TrendTemplate from "./Partials/TrendTemplate.vue";
import TrendSectionNav from "./Partials/TrendSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import { trendOptions } from "./Partials/trendOptions";
import { formatMoney } from "@/utils";

interface AccountEntry {
    id: number; name: string; currency_code: string; balance: number;
    base_balance: number | null; quote_balance: number | null;
    base_in_quote: number | null; is_multi_currency: boolean;
}
interface AccountGroup {
    group: string; accounts: AccountEntry[];
    subtotals: { base: number; quote: number; base_in_quote: number };
}
interface Goal {
    id: string; name: string; target_amount: number; current_balance: number;
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
    exchangeRate: number;
    baseCurrency: string;
    quoteCurrency: string;
    totals: Totals;
}>(), {
    accounts: () => [], pinnedGoals: () => [], availableGoals: () => [],
    pinnedGoalIds: () => [], exchangeRate: 59.8, baseCurrency: 'USD', quoteCurrency: 'DOP',
});

const localExchangeRate = ref(props.exchangeRate);
const isSavingRate = ref(false);
const saveSuccess = ref(false);
const showGoalPicker = ref(false);
const localPinnedIds = ref<string[]>([...props.pinnedGoalIds]);

const groupLabel = (group: string) => {
    const labels: Record<string, string> = {
        bank: 'Bank', savings: 'Savings', cash: 'Cash', cash_on_hand: 'Cash on Hand',
        credit_card: 'Credit Card', money_market: 'Money Market', other: 'Other',
    };
    return labels[group] ?? group;
};

const goalsTotal = computed(() =>
    props.pinnedGoals.reduce((sum, g) => sum + g.current_balance, 0)
);

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
    <AppLayout title="Financial Overview">
        <template #header>
            <TrendSectionNav :sections="trendOptions" />
        </template>

        <TrendTemplate title="Finance" :hide-panel="true">
            <div class="space-y-5 mt-5">

                <!-- Net Worth bar with inline exchange rate -->
                <section class="bg-primary text-white rounded-lg px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-6">
                        <div>
                            <p class="text-xs uppercase tracking-wide opacity-70">Net Worth</p>
                            <p class="text-2xl font-bold break-all leading-tight">
                                {{ formatMoney(totals.net_worth, quoteCurrency) }}
                            </p>
                        </div>
                        <div class="hidden md:flex items-center gap-4 text-sm opacity-80">
                            <span>{{ formatMoney(totals.total_quote, quoteCurrency) }}</span>
                            <span class="opacity-60">+</span>
                            <span>{{ formatMoney(totals.total_base, baseCurrency) }}
                                <span class="text-xs opacity-60">(≈ {{ formatMoney(totals.base_in_quote, quoteCurrency) }})</span>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs opacity-60">1 {{ baseCurrency }} =</span>
                        <input
                            v-model.number="localExchangeRate"
                            type="number" min="0" step="0.01"
                            class="w-20 px-2 py-1 text-sm rounded bg-white/20 text-white placeholder-white/50 border-0 focus:outline-none focus:ring-1 focus:ring-white/50"
                        />
                        <span class="text-xs opacity-60">{{ quoteCurrency }}</span>
                        <button
                            :disabled="isSavingRate || localExchangeRate === exchangeRate"
                            class="px-3 py-1 text-xs font-medium rounded bg-white/20 hover:bg-white/30 disabled:opacity-30 transition"
                            @click="saveExchangeRate"
                        >
                            {{ saveSuccess ? 'Saved!' : 'Update' }}
                        </button>
                    </div>
                </section>

                <!-- Side by side: Goals | Accounts -->
                <section class="flex flex-col lg:flex-row gap-4">

                    <!-- LEFT: Financial Goals (curated) -->
                    <div class="w-full lg:w-5/12">
                        <div class="bg-base-lvl-3 border border-base rounded-lg overflow-hidden h-full">
                            <div class="px-5 py-3 border-b border-base flex items-center justify-between">
                                <h2 class="font-bold text-body text-sm">Financial Goals</h2>
                                <LogerButton variant="inverse" class="text-xs" @click="showGoalPicker = !showGoalPicker">
                                    <IMdiPlus class="mr-1" v-if="!showGoalPicker" /> {{ showGoalPicker ? 'Done' : 'Add' }}
                                </LogerButton>
                            </div>

                            <!-- Goal picker -->
                            <div v-if="showGoalPicker" class="px-5 py-3 border-b border-base bg-base-lvl-1 space-y-2">
                                <p class="text-xs text-body-1/50 mb-2">Select goals to show in this view:</p>
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
                                    Save selection
                                </LogerButton>
                            </div>

                            <!-- Pinned goals table -->
                            <table v-if="pinnedGoals.length" class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs uppercase text-body-1/50 bg-base-lvl-2">
                                        <th class="text-left px-5 py-2 font-medium">Goal</th>
                                        <th class="text-right px-5 py-2 font-medium">Balance</th>
                                        <th class="text-right px-5 py-2 font-medium">Target</th>
                                        <th class="text-right px-5 py-2 font-medium">Diff</th>
                                        <th class="w-8"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="goal in pinnedGoals" :key="goal.id" class="border-b border-base">
                                        <td class="px-5 py-2 text-body">{{ goal.name }}</td>
                                        <td class="px-5 py-2 text-right tabular-nums">
                                            {{ formatMoney(goal.current_balance, quoteCurrency) }}
                                        </td>
                                        <td class="px-5 py-2 text-right text-body-1/60 tabular-nums">
                                            <span v-if="goal.target_amount">{{ formatMoney(goal.target_amount, quoteCurrency) }}</span>
                                            <span v-else>—</span>
                                        </td>
                                        <td class="px-5 py-2 text-right tabular-nums font-semibold"
                                            :class="(goal.current_balance - goal.target_amount) >= 0 ? 'text-green-500' : 'text-red-400'">
                                            <span v-if="goal.target_amount">
                                                {{ formatMoney(goal.current_balance - goal.target_amount, quoteCurrency) }}
                                            </span>
                                            <span v-else class="text-body-1/40 font-normal">—</span>
                                        </td>
                                        <td class="pr-3">
                                            <button @click="removeGoal(goal.id)" class="text-body-1/30 hover:text-red-400 transition" title="Remove from view">
                                                <IMdiClose class="text-xs" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-base-lvl-2 font-bold">
                                        <td class="px-5 py-2 text-body">Total</td>
                                        <td class="px-5 py-2 text-right tabular-nums">{{ formatMoney(goalsTotal, quoteCurrency) }}</td>
                                        <td></td><td></td><td></td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div v-else-if="!showGoalPicker" class="px-5 py-8 text-center text-body-1/40 text-sm">
                                Click <strong>Add</strong> to pin goals to this view.
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: Accounts by Bank -->
                    <div class="w-full lg:w-7/12">
                        <div class="bg-base-lvl-3 border border-base rounded-lg overflow-hidden h-full">
                            <div class="px-5 py-3 border-b border-base">
                                <h2 class="font-bold text-body text-sm">Accounts</h2>
                            </div>

                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs uppercase text-body-1/50 bg-base-lvl-2">
                                        <th class="text-left px-5 py-2 font-medium">Account</th>
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
                                            <td class="px-5 py-2 text-body pl-8">{{ account.name }}</td>
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
                                            <td class="px-5 py-1.5 text-xs font-semibold text-body-1/60 pl-8">Subtotal</td>
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
                                        <td class="px-5 py-3 text-body">Total</td>
                                        <td class="px-5 py-3 text-right tabular-nums">{{ formatMoney(totals.total_base, baseCurrency) }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums">{{ formatMoney(totals.total_quote + totals.base_in_quote, quoteCurrency) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </section>

            </div>
        </TrendTemplate>
    </AppLayout>
</template>
