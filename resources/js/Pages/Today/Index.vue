<script setup lang="ts">
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { format, parseISO } from 'date-fns';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import DayMonthToggle from '@/Components/molecules/DayMonthToggle.vue';
import { formatMoney } from '@/utils';
import { useTransactionModal } from '@/domains/transactions/useTransactionModal';
import { useToggleModal } from '@/domains/app/useToggleModal';

interface MoneyPayload {
    today_spent: number;
    daily_remaining: number;
    month_remaining: number;
    days_in_month_left: number;
    currency_code: string | null;
}

interface AttentionItem {
    id: string;
    message: string;
    cta: string | null;
    link: string | null;
}

interface TodayItem {
    kind: 'planner' | 'relationship';
    id: string;
    name: string | null;
    subtitle: string | null;
    status: string | null;
    total?: number | null;
    source?: string | null;
}

interface UpcomingItem {
    kind: 'billing_cycle' | 'utility' | 'planner';
    id: string;
    name: string | null;
    account_id: number | null;
    total: number | null;
    due_at: string;
    days_until: number;
    source?: string | null;
    notes?: string | null;
}

interface MealItem {
    id: number;
    meal_id: number | null;
    name: string | null;
    meal_type: string | null;
    is_liked: boolean;
    date: string;
    day_label: string;
}

const props = defineProps<{
    today: {
        money: MoneyPayload;
        attention: AttentionItem[];
        today: TodayItem[];
        upcoming: UpcomingItem[];
        meal: MealItem[];
    };
    landingPage: 'dashboard' | 'today';
}>();

const isLandingPage = computed(() => props.landingPage === 'today');

const toggleLandingPage = () => {
    router.patch(route('settings.landing-page'), {
        landing_page: isLandingPage.value ? 'dashboard' : 'today',
    }, { preserveScroll: true });
};

const { openTransactionModal } = useTransactionModal();

// Short day-of-week prefix on the date so a row's "when" reads in one
// glance ("Fri May 29 · tomorrow") instead of forcing the user to
// translate "May 29" → "what day is that?".
const formatDueDate = (iso: string) => {
    try {
        return format(parseISO(iso), 'EEE MMM d');
    } catch (e) {
        return iso;
    }
};

// "in 3 days" / "tomorrow" / "today" / "2 days overdue" — without depending on the
// raw date string (server already computed days_until against its own `now`).
const relativeDueLabel = (daysUntil: number) => {
    if (daysUntil < 0) return Math.abs(daysUntil) === 1 ? '1 day overdue' : `${Math.abs(daysUntil)} days overdue`;
    if (daysUntil === 0) return 'today';
    if (daysUntil === 1) return 'tomorrow';
    return `in ${daysUntil} days`;
};

const todayLabel = computed(() => format(new Date(), 'EEEE, MMM d'));

const openQuickAdd = () => {
    openTransactionModal({ mode: 'WITHDRAW' });
};

// Upcoming row navigation. Only kinds with a known detail destination are
// clickable; utilities and plan items currently have no per-record route,
// so they render as plain rows (no hover, no button semantics, no cursor).
const isUpcomingClickable = (item: UpcomingItem): boolean => {
    return item.kind === 'billing_cycle' && !!item.account_id;
};

const openUpcoming = (item: UpcomingItem): void => {
    if (item.kind === 'billing_cycle' && item.account_id) {
        router.visit(`/finance/accounts/${item.account_id}`);
    }
};

// Group meals by day for weekly view
const mealsByDay = computed(() => {
    const groups: Record<string, { label: string; meals: MealItem[] }> = {};
    for (const meal of props.today.meal) {
        if (!groups[meal.date]) {
            groups[meal.date] = { label: meal.day_label, meals: [] };
        }
        groups[meal.date].meals.push(meal);
    }
    return Object.entries(groups).sort(([a], [b]) => a.localeCompare(b));
});

// The modal itself is mounted app-wide in AppGlobals so the global +New menu can
// reach it too; Today just triggers the shared state.
const { openModal: openBulkPlanner } = useToggleModal('bulkPlanner');
</script>

<template>
    <AppLayout :title="$t('Today')">
        <main class="px-4 mx-auto mt-5 mb-10 max-w-screen-2xl sm:px-6 lg:px-8 space-y-4">
            <header class="flex items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-body">{{ $t('Today') }}</h1>
                    <p class="text-sm text-body-1/70 capitalize">{{ todayLabel }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <DayMonthToggle />
                    <button
                        type="button"
                        class="text-xs px-3 py-1.5 rounded-full border transition"
                        :class="isLandingPage
                            ? 'border-primary/30 bg-primary/10 text-primary'
                            : 'border-base text-body-1/60 hover:border-primary/30 hover:text-primary'"
                        :title="isLandingPage ? $t('Today is your landing page') : $t('Set Today as landing page')"
                        @click="toggleLandingPage"
                    >
                        <i class="fa fa-home mr-1" />
                        {{ isLandingPage ? $t('Landing page') : $t('Set as home') }}
                    </button>
                    <LogerButton variant="neutral" rounded @click="openBulkPlanner()">
                        <i class="fa fa-calendar-plus mr-2" />
                        {{ $t('Plan a batch') }}
                    </LogerButton>
                </div>
            </header>

            <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- TODAY'S SPEND widget — single number + log-an-expense action.
                     Intentionally narrow vs Dashboard's budget donut: Today is about
                     "did you log it?", not "where am I in the month". -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5 flex flex-col">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t("Today's spend") }}
                        </h2>
                        <Link href="/budgets" class="text-xs text-primary hover:underline">
                            {{ $t('Open budget') }} →
                        </Link>
                    </header>
                    <p class="text-3xl font-bold text-body">{{ formatMoney(today.money.today_spent) }}</p>
                    <p class="text-xs text-body-1/60 mt-1">
                        {{ today.money.today_spent > 0
                            ? $t('logged so far')
                            : $t('Nothing logged yet — log it before you forget.')
                        }}
                    </p>
                    <!-- Budget pace line.
                         Three states so the user always knows where they stand:
                           (a) under budget AND today's spend is within the daily slice → neutral
                           (b) under budget BUT today's spend exceeded the daily slice → amber pace warning
                           (c) over the monthly budget entirely → red over-by callout
                         Previous version hid the line whenever daily_remaining ≤ 0 — the
                         exact moment a "Today" page should be loudest. -->
                    <p
                        v-if="today.money.month_remaining > 0"
                        class="text-sm mt-2"
                        :class="today.money.today_spent > today.money.daily_remaining
                            ? 'text-warning font-medium'
                            : 'text-body-1/70'"
                    >
                        <template v-if="today.money.today_spent > today.money.daily_remaining">
                            {{ $t('Ahead of pace — daily slice is') }}
                            {{ formatMoney(today.money.daily_remaining) }}
                        </template>
                        <template v-else>
                            {{ formatMoney(today.money.daily_remaining) }}/{{ $t('day remaining') }}
                        </template>
                        <span class="text-xs text-body-1/50 ml-1">
                            ({{ today.money.days_in_month_left }}d left)
                        </span>
                    </p>
                    <p
                        v-else
                        class="text-sm mt-2 text-error font-medium"
                    >
                        {{ $t('Over budget by') }} {{ formatMoney(Math.abs(today.money.month_remaining)) }}
                        <span class="text-xs text-body-1/50 ml-1">
                            ({{ today.money.days_in_month_left }}d left)
                        </span>
                    </p>
                    <div class="mt-auto pt-4">
                        <LogerButton variant="inverse" rounded class="w-full" @click="openQuickAdd">
                            <i class="fa fa-plus mr-2" />
                            {{ $t('Log an expense') }}
                        </LogerButton>
                    </div>
                </article>

                <!-- ATTENTION widget — unread watchlist threshold alerts this month -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Attention') }}
                            <span v-if="today.attention.length" class="ml-1 text-error">({{ today.attention.length }})</span>
                        </h2>
                        <Link href="/notifications" class="text-xs text-primary hover:underline">
                            {{ $t('View all') }}
                        </Link>
                    </header>
                    <div v-if="today.attention.length" class="space-y-2">
                        <Link
                            v-for="item in today.attention"
                            :key="item.id"
                            :href="item.link || '/notifications'"
                            class="block p-2.5 rounded-md bg-error/5 border border-error/20 hover:border-error/40 transition"
                        >
                            <p class="text-sm font-medium text-body">{{ item.message }}</p>
                            <p v-if="item.cta" class="text-xs text-error mt-0.5">{{ item.cta }} →</p>
                        </Link>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">✅</div>
                        <p class="text-sm text-body-1">{{ $t('Nothing crossing thresholds') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Set a target on a watchlist to start getting alerts here.') }}
                        </p>
                    </div>
                </article>

                <!-- TODAY widget — Planner items due + overdue relationship reminders (FM-1) -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Due today') }}
                            <span v-if="today.today.length" class="ml-1 text-body-1">({{ today.today.length }})</span>
                        </h2>
                        <Link href="/relationships" class="text-xs text-primary hover:underline">
                            {{ $t('Relationships') }}
                        </Link>
                    </header>
                    <div v-if="today.today.length" class="space-y-2">
                        <div
                            v-for="item in today.today"
                            :key="item.id"
                            class="flex items-center gap-3 px-3 py-2 rounded-md bg-base-lvl-2"
                        >
                            <i
                                class="fa text-sm flex-shrink-0"
                                :class="item.kind === 'relationship'
                                    ? 'fa-heart text-error'
                                    : 'fa-circle text-primary'"
                            />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-body truncate">{{ item.name ?? $t('Item') }}</p>
                                <p v-if="item.subtitle" class="text-xs text-body-1/60 truncate">
                                    <span v-if="item.source" class="uppercase tracking-wide mr-1 text-body-1/40">{{ item.source }}</span>
                                    {{ item.subtitle }}
                                </p>
                                <p v-else-if="item.source" class="text-xs uppercase tracking-wide text-body-1/40">{{ item.source }}</p>
                            </div>
                            <span
                                v-if="item.total"
                                class="text-sm font-bold text-error flex-shrink-0"
                            >
                                {{ formatMoney(item.total) }}
                            </span>
                            <span
                                v-else
                                class="text-xs capitalize flex-shrink-0"
                                :class="item.kind === 'relationship' ? 'text-error font-medium' : 'text-body-1/60'"
                            >
                                {{ item.kind === 'relationship' ? item.subtitle : item.status }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">🎯</div>
                        <p class="text-sm text-body-1">{{ $t('No scheduled items today') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Planned transactions and overdue relationship reminders show up here.') }}
                        </p>
                    </div>
                </article>

                <!-- MEAL THIS WEEK widget — week's planned meals grouped by day. -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t("This week's menu") }}
                            <span v-if="today.meal.length" class="ml-1 text-body-1">({{ today.meal.length }})</span>
                        </h2>
                        <Link href="/meal-planner" class="text-xs text-primary hover:underline">
                            {{ $t('Open planner') }}
                        </Link>
                    </header>
                    <div v-if="mealsByDay.length" class="space-y-3">
                        <div v-for="[date, group] in mealsByDay" :key="date">
                            <p class="text-xs font-semibold text-body-1/50 uppercase tracking-wide mb-1">
                                {{ group.label }}
                            </p>
                            <div class="space-y-1">
                                <div
                                    v-for="meal in group.meals"
                                    :key="meal.id"
                                    class="flex items-center gap-3 px-3 py-2 rounded-md bg-base-lvl-2"
                                >
                                    <i class="fa fa-utensils text-sm text-secondary" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-body truncate">
                                            {{ meal.name ?? $t('Meal') }}
                                        </p>
                                        <p v-if="meal.meal_type" class="text-xs text-body-1/60 capitalize">{{ meal.meal_type }}</p>
                                    </div>
                                    <i v-if="meal.is_liked" class="fa fa-heart text-error" :title="$t('Favorite')" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">🍽️</div>
                        <p class="text-sm text-body-1">{{ $t('Nothing planned this week') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Schedule a meal from the planner — favorites get prioritized in the random meal picker.') }}
                        </p>
                    </div>
                </article>

                <!-- UPCOMING widget — bills + utilities due in next 7 days. v0.2 (HM-1):
                     also surfaces Occurrence type=utility records (water/electricity/etc.). -->
                <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
                    <header class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                            {{ $t('Upcoming') }}
                            <span v-if="today.upcoming.length" class="ml-1 text-body-1">({{ today.upcoming.length }})</span>
                        </h2>
                    </header>
                    <div v-if="today.upcoming.length" class="space-y-2">
                        <!-- Render as <button> only when the row is actually navigable.
                             Previously every row carried type="button" — invalid HTML on
                             the <div> branch — and only billing_cycle items had an
                             onClick, while utilities/planners showed the same shape so
                             users couldn't tell which rows did anything. -->
                        <component
                            :is="isUpcomingClickable(item) ? 'button' : 'div'"
                            v-for="item in today.upcoming"
                            :key="item.id"
                            v-bind="isUpcomingClickable(item) ? { type: 'button' } : {}"
                            class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-md bg-base-lvl-2 transition text-left"
                            :class="isUpcomingClickable(item) ? 'hover:bg-base-lvl-1 cursor-pointer' : 'cursor-default'"
                            @click="isUpcomingClickable(item) ? openUpcoming(item) : null"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <i
                                    class="fa text-sm"
                                    :class="{
                                        'fa-credit-card text-warning': item.kind === 'billing_cycle',
                                        'fa-bolt text-secondary': item.kind === 'utility',
                                        'fa-calendar-day text-primary': item.kind === 'planner',
                                    }"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-body truncate">
                                        <span v-if="item.kind === 'planner' && item.source" class="text-xs uppercase tracking-wide text-body-1/40 mr-1">{{ item.source }}</span>
                                        {{ item.name ?? $t('Item') }}
                                    </p>
                                    <p class="text-xs text-body-1/60">
                                        {{ formatDueDate(item.due_at) }}
                                        <span class="mx-1">·</span>
                                        <span :class="item.days_until < 0 ? 'text-error font-medium' : ''">
                                            {{ relativeDueLabel(item.days_until) }}
                                        </span>
                                        <template v-if="item.kind === 'planner' && item.notes">
                                            <span class="mx-1">·</span>
                                            <span class="italic">{{ item.notes }}</span>
                                        </template>
                                    </p>
                                </div>
                            </div>
                            <span
                                v-if="item.total !== null"
                                class="text-sm font-bold text-error flex-shrink-0"
                            >
                                {{ formatMoney(item.total) }}
                            </span>
                        </component>
                    </div>
                    <div v-else class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="text-3xl mb-1.5">📅</div>
                        <p class="text-sm text-body-1">{{ $t('Nothing on the horizon') }}</p>
                        <p class="text-xs text-body-1/60 mt-1 max-w-xs">
                            {{ $t('Credit card cycles, utilities, and scheduled items planned for the next month show up here.') }}
                        </p>
                    </div>
                </article>
            </section>
        </main>
    </AppLayout>
</template>
