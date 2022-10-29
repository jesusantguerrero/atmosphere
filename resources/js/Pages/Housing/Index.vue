<template>
    <AppLayout title="Home Projects">
        <template #header>
            <HouseSectionNav>
                  <template #actions>
                      <div>
                          <LogerButton variant="inverse" @click="submit()"> Add Check </LogerButton>
                      </div>
                  </template>
            </HouseSectionNav>
      </template>
      <div class="pt-16 pb-20 pl-6 max-w-screen-2xl">
          <div class="flex flex-col items-center justify-center p-10 mx-auto font-bold rounded-md h-92 bg-base-lvl-3 text-body-1 max-w-7xl">
            <h4>
                Coming soon
            </h4>
            <div class="space-y-4">
                <div>
                  Name - Last Occurrence Date - Current Count - Last Days Passed - AVG Days
                </div>
                <div>
                  Gas - 22 sept 2022 - 3 -  31 - 27.
                  <p>Notify on AVG</p>
                  <p>Notify on last count</p>
                </div>
                <div>
                  Leche - 22 sept 2022 - 3 -  31 - 27.
                  <p>Notify on AVG</p>
                  <p>Notify on last count</p>
                </div>
                <div>
                  Agua - 22 sept 2022 - 3 -  31 - 27.
                  <p>Notify on AVG</p>
                  <p>Notify on last count</p>
                </div>
                <div>
                  Wipers - 22 sept 2022 - 3 -  31 - 27.
                  <p>Notify on AVG</p>
                  <p>Notify on last count</p>
                </div>

                <div class="text-red-500">
                    <ul>
                        <li>
                            - Check Schedule to compare current with AVG or Last Day to send notification
                        </li>
                        <li>
                            - Add an automation action to add a new entry with params
                        </li>
                    </ul>

                </div>
            </div>
          </div>
      </div>
    </AppLayout>
</template>

<script setup>

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
</script>
