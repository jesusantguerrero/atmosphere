<script setup lang="ts">
import { differenceInCalendarDays, parseISO } from 'date-fns';

import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import CategoryItem from '@/domains/transactions/components/CategoryItem.vue';

import { IOccurrenceCheck } from "@/Components/Modules/occurrence/models";

defineProps<{
    checks: IOccurrenceCheck[];
}>()
const getDayDiff = (lastDay: string): number => {
    return differenceInCalendarDays(new Date(), parseISO(lastDay));
}
</script>

<template>
    <div class="px-2 py-2 bg-white rounded-md overflow-x-auto ic-scroller">
        <SectionTitle type="secondary" class="font-bold text-center">
          Occurrence Checks
        </SectionTitle>
        <section class="flex mt-4">
          <CategoryItem
            class="capitalize"
            v-for="occurrence in checks"
            :label="occurrence.name"
            :value="getDayDiff(occurrence.last_date)"
            wrap
          />
        </section>
      </div>
</template>
