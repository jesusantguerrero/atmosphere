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
      <div class="pt-16 pb-20 pl-6 max-w-screen-2xl">
          <div class="flex flex-col items-center justify-center py-10 mx-auto mt-4 font-bold rounded-md h-92 bg-base-lvl-3 text-body-1 max-w-7xl">
            Coming soon
          </div>
      </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { NDropdown } from 'naive-ui';

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

</script>
