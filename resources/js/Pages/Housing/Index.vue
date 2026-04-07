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
</script>

<template>
    <AppLayout title="Home Projects">
        <template #header>
            <HouseSectionNav>
                <template #actions>
                    <div class="flex gap-2">
                        <LogerButton variant="inverse" @click="router.visit('/housing/occurrence/create')">
                            Add Occurrence
                        </LogerButton>
                    </div>
                </template>
            </HouseSectionNav>
        </template>

        <main class="px-5 mx-auto mt-12 max-w-screen-2xl sm:px-6 lg:px-8 space-y-6 md:pr-16">
            <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-body">{{ totalOccurrences }}</p>
                    <p class="text-sm text-body-2 mt-1">Occurrences</p>
                </div>
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                    <p class="text-2xl font-bold" :class="overdueOccurrences > 0 ? 'text-red-500' : 'text-green-500'">
                        {{ overdueOccurrences }}
                    </p>
                    <p class="text-sm text-body-2 mt-1">Overdue</p>
                </div>
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center">
                    <p class="text-2xl font-bold text-body">{{ totalBoards }}</p>
                    <p class="text-sm text-body-2 mt-1">Boards</p>
                </div>
                <div class="bg-base-lvl-3 border border-base rounded-lg px-4 py-3 text-center flex items-center justify-center">
                    <Link href="/housing/occurrence" class="text-primary text-sm font-medium hover:underline">
                        View all occurrences
                    </Link>
                </div>
            </section>

            <section class="md:flex md:gap-6 space-y-6 md:space-y-0">
                <div class="md:w-1/2 space-y-4">
                    <OccurrenceWidget :checks="checks" />
                </div>

                <div class="md:w-1/2 space-y-4">
                    <WidgetTitleCard title="Chore Boards" :hide-divider="false">
                        <template #action>
                            <LogerButton
                                variant="inverse"
                                class="text-xs"
                                rounded
                                @click="router.visit('/housing/chores')"
                            >
                                View all
                            </LogerButton>
                        </template>

                        <div class="w-full">
                            <ChoreWidget v-if="boards.length" :boards="boards" />
                            <section v-else class="flex flex-col items-center justify-center py-8 w-full text-center">
                                <p class="text-body-2 text-sm mb-3">No chore boards created yet.</p>
                                <LogerButton variant="inverse" @click="router.visit('/housing/chores')">
                                    Add Chore List
                                </LogerButton>
                            </section>
                        </div>
                    </WidgetTitleCard>
                </div>
            </section>
        </main>
    </AppLayout>
</template>
