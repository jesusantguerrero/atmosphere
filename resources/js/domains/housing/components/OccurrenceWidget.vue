<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import CategoryItem from '@/domains/transactions/components/CategoryItem.vue';

import { IOccurrenceCheck } from "@/domains/housing/models";
import { getDayDiff } from '@/utils';

withDefaults(defineProps<{
    checks?: IOccurrenceCheck[];
}>(), {
    checks: () => ([])
})

const getUrgencyColor = (occurrence: IOccurrenceCheck): string => {
    const days = typeof getDayDiff(occurrence.last_date) === 'number'
        ? getDayDiff(occurrence.last_date) as number
        : 0;
    const avg = occurrence.avg_days_passed;

    if (!avg || avg <= 0) return '#9ca3af'; // gray — not enough data

    if (days >= avg + 3) return '#ef4444';   // red — overdue
    if (days >= avg) return '#f59e0b';        // amber — due now
    if (days >= avg - 3) return '#fb923c';    // orange — approaching
    return '#22c55e';                          // green — on track
};

const getUrgencyLabel = (occurrence: IOccurrenceCheck): string => {
    const days = typeof getDayDiff(occurrence.last_date) === 'number'
        ? getDayDiff(occurrence.last_date) as number
        : 0;
    const avg = occurrence.avg_days_passed;

    if (!avg || avg <= 0) return `${days}d`;
    const diff = days - avg;
    if (diff > 0) return `+${diff}d`;
    return `${days}d`;
};
</script>

<template>
    <div class="px-2 py-2 bg-white rounded-md overflow-x-auto ic-scroller">
        <SectionTitle type="secondary" class="font-bold text-center cursor-pointer">
            <Link href="/housing/occurrence">
                Occurrence Checks
            </Link>
        </SectionTitle>
        <section class="flex mt-4" v-if="checks?.length">
          <CategoryItem
                class="capitalize"
                v-for="occurrence in checks"
                :label="occurrence.name"
                :value="getUrgencyLabel(occurrence)"
                :color="getUrgencyColor(occurrence)"
                :color-class="''"
                wrap
          />
        </section>
        <section class="flex items-center flex-col justify-center" v-else>
          <CategoryItem
                class="capitalize"
                label=""
                value="NA"
                wrap
                @click="router.visit('/housing/occurrence')"
          >
            <template #icon>
                <span class=" font-bold text-2xl">
                    <IMdiTimerStarOutline />
                </span>
            </template>
          </CategoryItem>
          <p class="text-center text-sm -mt-6">Occurrences track the duration of events based on transactions or manual input</p>
        </section>
      </div>
</template>
