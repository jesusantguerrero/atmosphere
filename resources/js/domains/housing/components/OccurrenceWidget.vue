<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import SectionTitle from "@/Components/atoms/SectionTitle.vue";
import CategoryItem from '@/domains/transactions/components/CategoryItem.vue';

import { IOccurrenceCheck } from "@/domains/housing/models";
import { getDayDiff } from '@/utils';

defineProps<{
    checks: IOccurrenceCheck[];
}>()
</script>

<template>
    <div class="px-2 py-2 bg-white rounded-md overflow-x-auto ic-scroller">
        <SectionTitle type="secondary" class="font-bold text-center cursor-pointer">
            <Link href="/housing/occurrence">
                Occurrence Checks
            </Link>
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
