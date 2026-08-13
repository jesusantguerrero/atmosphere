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
    <AppLayout :title="$t('Chores')">
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
        <WelcomeCard v-else :message="$t('Chores')">
            <section class="flex flex-col items-center pb-12 mx-auto text-center">
                <img src="../LogerProfile/empty-box.svg" class="opacity-50" />
                <h4 class="text-lg font-bold text-body">{{ $t('No chores yet') }}</h4>
                <p class="max-w-md my-3 text-body-1">
                    {{ $t('Chores keep recurring household tasks visible and assignable to everyone at home. Create a list to start tracking who does what and when.') }}
                </p>

                <LogerButton variant="primary" @click="createList" :processing="createListForm.processing">
                    {{ $t('Create chore list') }}
                </LogerButton>
            </section>
        </WelcomeCard>
  </main>

    </AppLayout>
</template>
