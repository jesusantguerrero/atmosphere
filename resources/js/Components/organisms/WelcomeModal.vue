<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Modal from '@/Components/atoms/Modal.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

const STORAGE_KEY = 'loger-onboarding-seen';

const props = defineProps<{
    modules: any[];
}>();

const isVisible = ref(false);

onMounted(() => {
    const alreadySeen = localStorage.getItem(STORAGE_KEY);
    if (!alreadySeen) {
        isVisible.value = true;
    }
});

const dismiss = () => {
    localStorage.setItem(STORAGE_KEY, '1');
    isVisible.value = false;
};

interface ModuleCard {
    key: string;
    icon: string;
    title: string;
    description: string;
    link: string;
    linkLabel: string;
}

const allModuleCards: ModuleCard[] = [
    {
        key: 'finance',
        icon: 'fas fa-dollar-sign',
        title: 'Finance',
        description: 'Track income, expenses, and manage your accounts with full transaction history.',
        link: '/finance',
        linkLabel: 'Go to Finance',
    },
    {
        key: 'meals',
        icon: 'far fa-calendar-alt',
        title: 'Meal Planner',
        description: 'Plan your weekly meals, track ingredients, and stay on top of your nutrition.',
        link: '/meals/overview',
        linkLabel: 'Go to Meals',
    },
    {
        key: 'housing',
        icon: 'fas fa-home',
        title: 'Housing',
        description: 'Manage property tasks, maintenance schedules, and housing costs in one place.',
        link: '/housing',
        linkLabel: 'Go to Housing',
    },
];

const enabledModuleKeys = props.modules
    .filter((m) => m.enabled)
    .map((m) => m.name.toLowerCase());

const visibleCards = allModuleCards.filter((card) =>
    enabledModuleKeys.includes(card.key)
);
</script>

<template>
    <Modal :show="isVisible" max-width="xl" :closeable="true" @close="dismiss">
        <div class="bg-base-lvl-3 px-6 pt-6 pb-4">
            <div class="flex items-start justify-between mb-1">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-primary/10 text-primary">
                        <i class="fas fa-rocket text-lg" />
                    </div>
                    <h2 class="text-xl font-bold text-body-1">Welcome to Loger!</h2>
                </div>
                <button
                    class="text-body-1/50 hover:text-body-1 transition-colors"
                    @click="dismiss"
                    aria-label="Close"
                >
                    <i class="fas fa-times" />
                </button>
            </div>
            <p class="text-sm text-body-1/70 mb-5 ml-13">
                Here's a quick overview of what you can do
            </p>

            <div class="grid gap-3" :class="visibleCards.length > 1 ? 'sm:grid-cols-2' : 'grid-cols-1'">
                <Link
                    v-for="card in visibleCards"
                    :key="card.key"
                    :href="card.link"
                    class="flex items-start gap-3 p-4 rounded-lg border border-base-lvl-2 bg-base-lvl-2 hover:border-primary/40 hover:bg-primary/5 transition-all group"
                    @click="dismiss"
                >
                    <div class="flex items-center justify-center w-9 h-9 rounded-md bg-primary/10 text-primary flex-shrink-0 group-hover:bg-primary group-hover:text-white transition-all">
                        <i :class="card.icon" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-body-1 text-sm">{{ card.title }}</p>
                        <p class="text-xs text-body-1/60 mt-0.5 leading-relaxed">{{ card.description }}</p>
                        <span class="mt-1 inline-block text-xs font-semibold text-primary group-hover:underline">
                            {{ card.linkLabel }} &rarr;
                        </span>
                    </div>
                </Link>
            </div>
        </div>

        <footer class="flex items-center justify-between px-6 py-4 bg-base-lvl-3 border-t border-base-lvl-2">
            <button
                class="text-sm text-body-1/50 hover:text-body-1 transition-colors"
                @click="dismiss"
            >
                Skip tour
            </button>
            <LogerButton variant="primary" @click="dismiss">
                Show me around
            </LogerButton>
        </footer>
    </Modal>
</template>
