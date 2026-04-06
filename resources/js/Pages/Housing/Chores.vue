<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from "@inertiajs/vue3"

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import BoardSection from '@/Components/board/BoardSection.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';

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

const createListForm = useForm({})
const createList = () => {
    return createListForm.post(location.pathname)
}

</script>

<template>
    <AppLayout title="Occurrence Checks">
        <template #header>
            <HouseSectionNav class="h-12" />
      </template>
      <main class="mt-4">
        <BoardSection
            v-if="chorePlan"
            class="w-full pt-12 mx-2 overflow-hidden"
            :board="chorePlan"
            :users="users"
            :automations="automations"
            :filters="filters"
        />
        <WelcomeCard v-else message="Loger profiles">
            <section class="flex flex-col items-center pb-12 mx-auto">
                <img src="../LogerProfile/empty-box.svg" class="opacity-50" />
                <h4 class="text-lg font-bold text-body-1"> There are not chores created</h4>
                <p class="max-w-lg my-3">
                    Lorem ipsum dolor sit amet consectetur adipiscing elit maecenas, ligula rutrum scelerisque vulputate netus urna dui facilisi, habitant auctor sapien a porta nisi arcu. Imperdiet curabitur accumsan torquent fusce mauris blandit fames mollis primis lacinia felis, interdum p.
                </p>

                <LogerButton variant="inverse" @click="createList" :processing="createListForm.processing">
                    Create new chore list
                </LogerButton>
            </section>
        </WelcomeCard>
  </main>

    </AppLayout>
</template>
