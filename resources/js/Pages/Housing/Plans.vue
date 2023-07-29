<template>
    <AppLayout title="Occurrence Checks">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div>
                        <LogerButton variant="inverse" @click="isModalOpen = !isModalOpen">
                            Add Plan
                        </LogerButton>
                      </div>
                  </template>
            </HouseSectionNav>
      </template>
      <main class="pt-16 pb-20 pl-6 max-w-screen-2xl">
            <BoardSection
                v-if="board"
                class="w-full pt-12 mx-2 overflow-hidden"
                :board="board"
                :users="users"
                :automations="automations"
                :filters="filters"
            />
            <section
                v-else
            >
                Create Plans
            </section>
      </main>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { NDropdown } from 'naive-ui';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import BoardSection from '@/Components/board/BoardSection.vue';

</script>
