<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from "@inertiajs/vue3"

import AppLayout from '@/Components/templates/AppLayout.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import BoardSection from '@/Components/board/BoardSection.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import ChoreAreasView from '@/Components/board/views/ChoreAreasView.vue';

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

// Deep-link / default the board view via ?view= (e.g. ?view=family for the
// kitchen-screen view). BoardSection reads this through its `layout` prop.
const initialView = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('view') || 'family';

// «Áreas | Lista» switch. Áreas (Tody-style) is the default; ?tab=list
// opens the board.
type ChoresTab = 'areas' | 'list';
const choreTabs: { key: ChoresTab; label: string }[] = [
    { key: 'areas', label: 'Areas' },
    { key: 'list', label: 'List' },
];
const initialTab = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '').get('tab');
const activeTab = ref<ChoresTab>(initialTab === 'list' ? 'list' : 'areas');

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
        <div v-if="chorePlan" class="flex justify-center px-2">
            <div class="inline-flex gap-1 p-1 rounded-full bg-base-lvl-2" role="tablist">
                <button
                    v-for="tab in choreTabs"
                    :key="tab.key"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.key"
                    class="px-4 py-1.5 text-sm font-semibold transition rounded-full"
                    :class="activeTab === tab.key ? 'bg-base-lvl-3 text-body shadow-sm' : 'text-body-1/60 hover:text-body'"
                    @click="activeTab = tab.key"
                >
                    {{ $t(tab.label) }}
                </button>
            </div>
        </div>
        <ChoreAreasView
            v-if="chorePlan && activeTab === 'areas'"
            class="px-2 pt-6 mx-auto max-w-5xl"
            :board-id="chorePlan.id"
        />
        <BoardSection
            v-else-if="chorePlan"
            class="w-full pt-12 mx-2 overflow-hidden"
            :board="chorePlan"
            :users="users"
            :automations="automations"
            :filters="filters"
            :layout="initialView"
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
