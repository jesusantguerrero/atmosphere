<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { formatDate, formatMoney } from '@/utils';

/**
 * Upcoming — cross-pillar timeline: billing cycles, utilities, and planner items
 * due in the next ~month. Lifted from Pages/Today/Index.vue (HM-1) so the Dashboard
 * can absorb its planning-side reach (Next Payments is finance-only, this isn't).
 *
 * Reads `upcoming` from TodayService payload.
 */

export interface UpcomingItem {
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

withDefaults(defineProps<{
    items: UpcomingItem[];
}>(), {
    items: () => [],
});

const formatDueDate = (iso: string) => {
    try {
        return formatDate(iso, undefined, 'EEE MMM d');
    } catch {
        return iso;
    }
};

const relativeDueLabel = (daysUntil: number) => {
    if (daysUntil < 0) return Math.abs(daysUntil) === 1 ? '1 day overdue' : `${Math.abs(daysUntil)} days overdue`;
    if (daysUntil === 0) return 'today';
    if (daysUntil === 1) return 'tomorrow';
    return `in ${daysUntil} days`;
};

const isClickable = (item: UpcomingItem): boolean => {
    return item.kind === 'billing_cycle' && !!item.account_id;
};

const open = (item: UpcomingItem): void => {
    if (item.kind === 'billing_cycle' && item.account_id) {
        router.visit(`/finance/accounts/${item.account_id}`);
    }
};
</script>

<template>
    <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
        <header class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                {{ $t('Upcoming') }}
                <span v-if="items.length" class="ml-1 text-body-1">({{ items.length }})</span>
            </h2>
        </header>

        <div v-if="items.length" class="space-y-2">
            <component
                :is="isClickable(item) ? 'button' : 'div'"
                v-for="item in items"
                :key="item.id"
                v-bind="isClickable(item) ? { type: 'button' } : {}"
                class="w-full flex items-center justify-between gap-3 px-3 py-2 rounded-md bg-base-lvl-2 transition text-left"
                :class="isClickable(item) ? 'hover:bg-base-lvl-1 cursor-pointer' : 'cursor-default'"
                @click="isClickable(item) ? open(item) : null"
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
                <span v-if="item.total !== null" class="text-sm font-bold text-error flex-shrink-0">
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
</template>
