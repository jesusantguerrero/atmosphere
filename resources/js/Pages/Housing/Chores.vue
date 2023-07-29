<template>
    <AppLayout title="Occurrence Checks">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div>
                          <LogerButton variant="inverse" @click="isModalOpen = !isModalOpen"> Add Check </LogerButton>
                      </div>
                  </template>
            </HouseSectionNav>
      </template>
      <main class="pt-16 pb-20 pl-6 max-w-screen-2xl">
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

<script setup>
import { ref, computed } from 'vue';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import BoardSection from '@/Components/board/BoardSection.vue';

const props = defineProps({
    chores: {
        type: Array,
        default() {
            return []
        }
    }
})

const isModalOpen = ref(false);

const chorePlan = computed(() => {
    return props.chores.data?.at(0)
})

</script>
