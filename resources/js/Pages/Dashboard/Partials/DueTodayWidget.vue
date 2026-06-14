<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatMoney } from '@/utils';

/**
 * Due Today — planner items scheduled for today + overdue relationship reminders.
 * Lifted from Pages/Today/Index.vue (FM-1) so the Dashboard can absorb its useful
 * widgets and we can retire the /today route. Reads `today` from TodayService payload.
 */

export interface TodayItem {
    kind: 'planner' | 'relationship';
    id: string;
    name: string | null;
    subtitle: string | null;
    status: string | null;
    total?: number | null;
    source?: string | null;
}

withDefaults(defineProps<{
    items: TodayItem[];
}>(), {
    items: () => [],
});
</script>

<template>
    <article class="bg-base-lvl-3 rounded-lg border border-base p-5">
        <header class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                {{ $t('Due today') }}
                <span v-if="items.length" class="ml-1 text-body-1">({{ items.length }})</span>
            </h2>
            <Link href="/relationships" class="text-xs text-primary hover:underline">
                {{ $t('Relationships') }}
            </Link>
        </header>

        <div v-if="items.length" class="space-y-2">
            <div
                v-for="item in items"
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
                <span v-if="item.total" class="text-sm font-bold text-error flex-shrink-0">
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
</template>
