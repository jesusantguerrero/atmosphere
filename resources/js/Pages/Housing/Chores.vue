<script setup lang="ts">
import { ref, computed } from 'vue';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import BoardSection from '@/Components/board/BoardSection.vue';

interface Plan {
    id: number;
}

const props = defineProps<{
    chores: Plan[];
    users: Record<string, string>[];
    filters: string[];
    automations: string[];
}>()

const isModalOpen = ref(false);

const chorePlan = computed(() => {
    return props.chores?.at(0)
})

</script>

<template>
    <AppLayout title="Occurrence Checks">
        <template #header>
            <HouseSectionNav class="h-12" />
      </template>
      <main class="mt-4">
        <BoardSection
            class="w-full pt-12 mx-2 overflow-hidden"
            :board="chorePlan"
            :users="users"
            :automations="automations"
            :filters="filters"
        />
  </main>

    </AppLayout>
</template>
