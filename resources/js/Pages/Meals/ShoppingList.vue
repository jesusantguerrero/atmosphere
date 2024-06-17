<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from "@inertiajs/vue3"

import AppLayout from '@/Components/templates/AppLayout.vue';
import StatusButtons from "@/Components/molecules/StatusButtons.vue";

import BoardSection from '@/Components/board/BoardSection.vue';
import BoardItemContainer from '@/Components/board/BoardItemContainer.vue';
import MealSectionNav from '@/domains/meal/components/MealSectionNav.vue';

interface Plan {
    id: number;
}

const props = defineProps<{
    chores: Plan[];
    users: Record<string, string>[];
    filters: string[];
    automations: string[];
    serverSearchOptions: Record<string, any>
}>()


const chorePlan = computed(() => {
    return props.chores?.at(0)
})

const onSearch = (query: string) => {
    router.replace(`${location.pathname}/${query}`)
}

console.log(props.chores);

const selectedStage = ref();
const tasks = computed(() => {
  return props.chores.at(0).stages.at(0)?.items
});

const inbox = computed(() => {
  const inbox = selectedStage.value
    ? tasks.value.filter((task: Record<string, any>) => task.stage == selectedStage.value)
    : tasks.value.filter((task: Record<string, any>) => task);
  return inbox;
});
const committed = computed(() => {
  const inbox = selectedStage.value
    ? tasks.value.filter((task: Record<string, any>) => task.stage == selectedStage.value)
    : tasks.value.filter((task: Record<string, any>) => task);
  return inbox;
});

const mealStatus = {
  list: {
    label: "List",
    value: "list",
  },
  summary: {
    label: "summary",
    value: "summary",
  },
};

const currentStatus = ref(props.serverSearchOptions?.filters?.is_liked || "summary");
</script>

<template>
    <AppLayout title="Shopping List">
        <template #header>
            <MealSectionNav class="h-12" >
                <template #actions>
                    <StatusButtons
                        v-model="currentStatus"
                        :statuses="mealStatus"
                    />
                </template>
                </MealSectionNav>
      </template>
      <main class="mt-4">
        <template v-if="chorePlan">
            <BoardSection
                :layout="currentStatus"
                class="w-full pt-12 mx-2 overflow-hidden"
                :board="chorePlan"
                :users="users"
                :automations="automations"
                :filters="filters"
                :multi-stage="false"
                @search="onSearch"
                resource-name="items"
            />
            <!-- <BoardItemContainer
                v-else
                title="To Do"
                :allow-add="true"
                :boards="[]"
                :tasks="inbox"
              >
                <template #empty v-if="committed.length">
                  <div class="w-full mx-auto prose prose-xl text-center">
                    <img
                      src="../../../img/undraw_a_day_at_the_park.svg"
                      class="w-4/12 mx-auto"
                    />
                    <p class="mt-4">All tasks done. take a rest</p>
                  </div>
                </template>

                <template #empty v-else>
                  <div class="w-full mx-auto prose prose-xl text-center">
                    <img src="../../../img/undraw_empty.svg" class="w-4/12 mx-auto" />
                    <small class="mt-4 text-gray-400">
                      Nothing to do. Add new tasks from here or mark in your
                      <a href="#" @click="openBoards">boards</a> as todo</small
                    >
                  </div>
                </template>
            </BoardItemContainer> -->
        </template>
        <WelcomeCard  v-else message="Loger profiles">
            <section class="flex flex-col items-center pb-12 mx-auto">
                <img src="../LogerProfile/empty-box.svg" class="opacity-50" />
                <h4 class="text-lg font-bold text-body-1"> There are not profiles created</h4>
                <p class="max-w-lg my-3">
                    Lorem ipsum dolor sit amet consectetur adipiscing elit maecenas, ligula rutrum scelerisque vulputate netus urna dui facilisi, habitant auctor sapien a porta nisi arcu. Imperdiet curabitur accumsan torquent fusce mauris blandit fames mollis primis lacinia felis, interdum p.
                </p>
                <LogerButton variant="inverse" @click="isModalOpen = !isModalOpen">
                    Add shopping list
                </LogerButton>
            </section>
        </WelcomeCard>
  </main>

    </AppLayout>
</template>
