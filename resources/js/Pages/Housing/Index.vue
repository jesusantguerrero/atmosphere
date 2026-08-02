<script lang="ts" setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import OccurrenceWidget from '@/domains/housing/components/OccurrenceWidget.vue';
import ChoreWidget from '@/Components/widgets/ChoreWidget.vue';
import WidgetTitleCard from '@/Components/molecules/WidgetTitleCard.vue';

import { IOccurrenceCheck, IBoard } from '@/domains/housing/models';
import { getDayDiff } from '@/utils';

const props = defineProps<{
    checks: IOccurrenceCheck[];
    boards: IBoard[];
}>();

const totalOccurrences = computed(() => props.checks.length);

const overdueOccurrences = computed(() => {
    return props.checks.filter((occurrence) => {
        const days = typeof getDayDiff(occurrence.last_date) === 'number'
            ? getDayDiff(occurrence.last_date) as number
            : 0;
        return occurrence.avg_days_passed > 0 && days >= occurrence.avg_days_passed;
    }).length;
});

const totalBoards = computed(() => props.boards.length);

const isFirstRun = computed(() => totalOccurrences.value === 0 && totalBoards.value === 0);
</script>

<template>
    <AppLayout :title="$t('Household')">
        <template #header>
            <HouseSectionNav>
                <template #actions>
                    <div class="flex gap-2">
                        <LogerButton variant="inverse" @click="router.visit('/housing/occurrence/create')">
                            {{ $t('Add Reminder') }}
                        </LogerButton>
                    </div>
                </template>
            </HouseSectionNav>
        </template>

        <main class="px-5 mx-auto pt-16 max-w-screen-2xl sm:px-6 lg:px-8 space-y-6 md:pr-16">
            <section
                v-if="isFirstRun"
                class="flex flex-col items-center justify-center max-w-xl py-16 mx-auto text-center"
            >
                <div class="flex items-center justify-center w-16 h-16 mb-5 rounded-full bg-primary/10 text-primary">
                    <i class="text-2xl fa fa-house-user"></i>
                </div>
                <h2 class="text-xl font-bold text-body">{{ $t('Welcome to your Household') }}</h2>
                <p class="max-w-md mt-2 mb-6 text-body-2">
                    {{ $t('Keep your home running: track recurring tasks like oil changes or filter swaps, and share chores the whole family can pick up.') }}
                </p>
                <div class="flex flex-col gap-3 sm:flex-row">
                    <LogerButton variant="inverse" @click="router.visit('/housing/occurrence/create')">
                        {{ $t('Add Reminder') }}
                    </LogerButton>
                    <LogerButton variant="neutral" @click="router.visit('/housing/chores')">
                        {{ $t('Add chore list') }}
                    </LogerButton>
                </div>
            </section>

            <section v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-body">{{ totalOccurrences }}</p>
                    <p class="text-sm text-body-2 mt-1">{{ $t('Reminders') }}</p>
                </div>
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                    <p class="text-2xl font-bold" :class="overdueOccurrences > 0 ? 'text-error' : 'text-success'">
                        {{ overdueOccurrences }}
                    </p>
                    <p class="text-sm text-body-2 mt-1">{{ $t('Overdue') }}</p>
                </div>
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-body">{{ totalBoards }}</p>
                    <p class="text-sm text-body-2 mt-1">{{ $t('Chore boards') }}</p>
                </div>
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center flex items-center justify-center">
                    <Link href="/housing/occurrence" class="text-primary text-sm font-medium hover:underline">
                        {{ $t('View all reminders') }}
                    </Link>
                </div>
            </section>

            <section v-if="!isFirstRun" class="md:flex md:gap-6 space-y-6 md:space-y-0">
                <div class="md:w-1/2 space-y-4">
                    <OccurrenceWidget :checks="checks" />
                </div>

                <div class="md:w-1/2 space-y-4">
                    <WidgetTitleCard :title="$t('Chore boards')" :hide-divider="false">
                        <template #action>
                            <LogerButton
                                variant="inverse"
                                class="text-xs"
                                rounded
                                @click="router.visit('/housing/chores')"
                            >
                                {{ $t('View all') }}
                            </LogerButton>
                        </template>

                        <div class="w-full">
                            <ChoreWidget v-if="boards.length" :boards="boards" />
                            <section v-else class="flex flex-col items-center justify-center py-8 w-full text-center">
                                <p class="text-body-2 text-sm mb-3">{{ $t('No chore boards yet.') }}</p>
                                <LogerButton variant="inverse" @click="router.visit('/housing/chores')">
                                    {{ $t('Add chore list') }}
                                </LogerButton>
                            </section>
                        </div>
                    </WidgetTitleCard>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
