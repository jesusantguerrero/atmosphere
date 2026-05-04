<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

import WidgetContainer from '@/Components/WidgetContainer.vue';
import { formatMoney } from '@/utils';

interface MonthStat {
    total: number | string;
    projected?: number | string;
    is_current_period?: boolean;
}

interface WatchlistEntry {
    id: number;
    name: string;
    type: string;
    target: number | string;
    data: { month: MonthStat; prevMonth?: MonthStat };
}

const props = defineProps<{
    watchlists: WatchlistEntry[];
}>();

const items = computed(() =>
    (props.watchlists ?? []).map((item) => {
        const target = Number(item.target ?? 0);
        const total = Number(item.data?.month?.total ?? 0);
        const ratio = target > 0 ? total / target : 0;
        const status = !target ? 'neutral' : ratio > 1 ? 'red' : ratio > 0.7 ? 'amber' : 'green';
        return {
            id: item.id,
            name: item.name,
            total,
            target,
            ratio,
            status,
            barWidth: Math.min(ratio, 1) * 100,
        };
    })
);

const trafficLightClass: Record<string, string> = {
    red: 'bg-error',
    amber: 'bg-warning',
    green: 'bg-success',
    neutral: 'bg-base-lvl-1',
};

const trafficLightTextClass: Record<string, string> = {
    red: 'text-error',
    amber: 'text-warning',
    green: 'text-success',
    neutral: 'text-body-1',
};

const navigateTo = (id: number) => {
    router.visit(`/finance/watchlist/${id}`);
};

const navigateToList = () => {
    router.visit('/finance/watchlist');
};
</script>

<template>
    <WidgetContainer v-if="items.length" :message="$t('Watchlists')">
        <template #actions>
            <button
                class="text-xs text-primary hover:text-primary/80 px-3"
                @click="navigateToList"
            >
                {{ $t('View all') }}
            </button>
        </template>
        <template #content>
            <div class="divide-y divide-base">
                <button
                    v-for="item in items"
                    :key="item.id"
                    class="w-full flex flex-col gap-1.5 px-5 py-2.5 hover:bg-base-lvl-2 transition text-left"
                    @click="navigateTo(item.id)"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span
                                class="w-2 h-2 rounded-full flex-shrink-0"
                                :class="trafficLightClass[item.status]"
                            />
                            <span class="text-sm font-medium text-body truncate">{{ item.name }}</span>
                        </div>
                        <span
                            class="text-xs font-semibold flex-shrink-0"
                            :class="trafficLightTextClass[item.status]"
                        >
                            {{ Math.round(item.ratio * 100) }}%
                        </span>
                    </div>
                    <div class="h-1 rounded-full bg-base-lvl-1 overflow-hidden">
                        <div
                            class="h-full transition-all"
                            :class="trafficLightClass[item.status]"
                            :style="{ width: `${item.barWidth}%` }"
                        />
                    </div>
                    <div class="text-xs text-body-1/60">
                        {{ formatMoney(item.total) }} / {{ formatMoney(item.target) }}
                    </div>
                </button>
            </div>
        </template>
    </WidgetContainer>
</template>
