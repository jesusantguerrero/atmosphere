<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from "@inertiajs/vue3"

import AppLayout from '@/Components/templates/AppLayout.vue';
import StatusButtons from "@/Components/molecules/StatusButtons.vue";

import BoardSection from '@/Components/board/BoardSection.vue';
import MealSectionNav from '@/domains/meal/components/MealSectionNav.vue';

interface Plan {
    id: number;
}

const props = defineProps<{
    chores: Plan[];
    users: Record<string, string>[];
    filters: string[];
    automations?: string[];
    serverSearchOptions: Record<string, any>
}>()


const chorePlan = computed(() => {
    return props.chores?.at(0)
})

const onSearch = (query: string) => {
    router.replace(`${location.pathname}/${query}`)
}

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
