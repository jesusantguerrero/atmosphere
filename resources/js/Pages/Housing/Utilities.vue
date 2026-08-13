<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

interface Utility {
    id: number;
    name: string;
    last_date: string | null;
    avg_days_passed: number | null;
    due_at: string | null;
    days_until: number | null;
    is_overdue: boolean;
    is_active: boolean;
}

const props = defineProps<{
    utilities: Utility[];
}>();

const overdue = computed(() => props.utilities.filter(u => u.is_overdue).length);

const badgeClass = (utility: Utility): string => {
    if (utility.days_until === null) return 'bg-base-lvl-1 text-body-1 dark:bg-gray-700 dark:text-body-1/70';
    if (utility.is_overdue) return 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400';
    if (utility.days_until <= 3) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400';
    return 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400';
};

const badgeLabel = (utility: Utility): string => {
    if (utility.days_until === null) return 'No data';
    if (utility.is_overdue) return `${Math.abs(utility.days_until)}d overdue`;
    if (utility.days_until === 0) return 'Due today';
    return `${utility.days_until}d left`;
};
</script>

<template>
    <AppLayout :title="$t('Utilities')">
        <template #header>
            <HouseSectionNav class="h-12">
                <template #actions>
                    <div class="flex gap-2">
                        <LogerButton
                            variant="inverse"
                            @click="router.visit('/housing/occurrence/create')"
                        >
                            Add Utility
                        </LogerButton>
                    </div>
                </template>
            </HouseSectionNav>
        </template>

        <main class="px-5 mx-auto mt-12 max-w-screen-2xl sm:px-6 lg:px-8 space-y-6 md:pr-16">
            <section v-if="utilities.length" class="space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                        <p class="text-2xl font-bold text-body">{{ utilities.length }}</p>
                        <p class="text-sm text-body-2 mt-1">{{ $t('Utilities') }}</p>
                    </div>
                    <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                        <p class="text-2xl font-bold" :class="overdue > 0 ? 'text-red-500' : 'text-green-500'">
                            {{ overdue }}
                        </p>
                        <p class="text-sm text-body-2 mt-1">{{ $t('Overdue') }}</p>
                    </div>
                </div>

                <div class="bg-base-lvl-3 border border-base rounded-lg overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-base text-left">
                                <th class="px-4 py-3 font-medium text-body-2">{{ $t('Name') }}</th>
                                <th class="px-4 py-3 font-medium text-body-2">{{ $t('Last Paid') }}</th>
                                <th class="px-4 py-3 font-medium text-body-2">{{ $t('Avg Cycle') }}</th>
                                <th class="px-4 py-3 font-medium text-body-2">{{ $t('Next Due') }}</th>
                                <th class="px-4 py-3 font-medium text-body-2">{{ $t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base">
                            <tr
                                v-for="utility in utilities"
                                :key="utility.id"
                                class="hover:bg-base-lvl-2 dark:hover:bg-base-lvl-2 cursor-pointer transition-colors"
                                @click="router.visit(`/housing/occurrence/${utility.id}/edit`)"
                            >
                                <td class="px-4 py-3 font-medium text-body">{{ utility.name }}</td>
                                <td class="px-4 py-3 text-body-2">{{ utility.last_date ?? '—' }}</td>
                                <td class="px-4 py-3 text-body-2">
                                    {{ utility.avg_days_passed ? `${utility.avg_days_passed}d` : '—' }}
                                </td>
                                <td class="px-4 py-3 text-body-2">{{ utility.due_at ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        :class="badgeClass(utility)"
                                    >
                                        {{ badgeLabel(utility) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section v-else class="flex flex-col items-center justify-center py-20 text-center space-y-4">
                <p class="text-body-2 text-base">{{ $t('No utilities tracked yet.') }}</p>
                <LogerButton variant="inverse" @click="router.visit('/housing/occurrence/create')">
                    {{ $t('Add your first utility') }}
                </LogerButton>
            </section>
        </main>
    </AppLayout>
</template>
