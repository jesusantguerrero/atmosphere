<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from "@inertiajs/vue3"
import axios from 'axios';

import AppLayout from '@/Components/templates/AppLayout.vue';
import StatusButtons from "@/Components/molecules/StatusButtons.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

import BoardSection from '@/Components/board/BoardSection.vue';
import MealSectionNav from '@/domains/meal/components/MealSectionNav.vue';

interface Plan {
    id: number;
}

interface Profile {
    id: number;
    name: string;
}

const props = defineProps<{
    chores: Plan[];
    users: Record<string, string>[];
    filters: string[];
    automations?: string[];
    serverSearchOptions: Record<string, any>;
    profiles: Profile[];
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

// Profile filter
const selectedProfileId = ref<number | null>(null);

const filteredChores = computed(() => {
    if (!chorePlan.value || selectedProfileId.value === null) {
        return props.chores;
    }
    const plan = chorePlan.value as any;
    if (!plan?.stages) return props.chores;

    return [{
        ...plan,
        stages: plan.stages.map((stage: any) => ({
            ...stage,
            items: stage.items.filter((item: any) => item.profile_id === selectedProfileId.value),
        })),
    }];
});

// Share state
const shareUrl = ref<string | null>(null);
const isSharing = ref(false);
const isCopied = ref(false);

const handleShare = async () => {
    isSharing.value = true;
    try {
        const response = await axios.post(route('meals.shoppingList.share'));
        shareUrl.value = response.data.url;
    } catch (e) {
        console.error('Failed to share list', e);
    } finally {
        isSharing.value = false;
    }
};

const handleUnshare = async () => {
    try {
        await axios.post(route('meals.shoppingList.unshare'));
        shareUrl.value = null;
    } catch (e) {
        console.error('Failed to unshare list', e);
    }
};

const copyShareUrl = async () => {
    if (!shareUrl.value) return;
    await navigator.clipboard.writeText(shareUrl.value);
    isCopied.value = true;
    setTimeout(() => { isCopied.value = false; }, 2000);
};
</script>

<template>
    <AppLayout title="Shopping List">
        <template #header>
            <MealSectionNav class="h-12">
                <template #actions>
                    <div class="flex items-center gap-2 ml-auto">
                        <StatusButtons
                            v-model="currentStatus"
                            :statuses="mealStatus"
                        />
                        <LogerButton
                            variant="inverse"
                            class="h-8 text-sm"
                            :processing="isSharing"
                            @click="shareUrl ? handleUnshare() : handleShare()"
                        >
                            {{ shareUrl ? 'Stop sharing' : 'Share' }}
                        </LogerButton>
                    </div>
                </template>
            </MealSectionNav>
        </template>

        <main class="mt-4">
            <!-- Share URL banner -->
            <div v-if="shareUrl" class="flex items-center gap-3 mx-4 mb-4 px-4 py-3 rounded-lg bg-primary/10 border border-primary/20">
                <span class="text-sm text-body flex-1 truncate">{{ shareUrl }}</span>
                <button
                    class="text-sm font-semibold text-primary whitespace-nowrap"
                    @click="copyShareUrl"
                >
                    {{ isCopied ? 'Copied!' : 'Copy link' }}
                </button>
            </div>

            <!-- Profile filter tabs -->
            <div v-if="profiles && profiles.length > 0" class="flex items-center gap-2 px-4 mb-4 overflow-x-auto">
                <button
                    class="px-3 py-1.5 rounded-full text-sm font-semibold border transition"
                    :class="selectedProfileId === null ? 'bg-primary text-white border-primary' : 'bg-base-lvl-2 text-body border-transparent'"
                    @click="selectedProfileId = null"
                >
                    All
                </button>
                <button
                    v-for="profile in profiles"
                    :key="profile.id"
                    class="px-3 py-1.5 rounded-full text-sm font-semibold border transition whitespace-nowrap"
                    :class="selectedProfileId === profile.id ? 'bg-primary text-white border-primary' : 'bg-base-lvl-2 text-body border-transparent'"
                    @click="selectedProfileId = profile.id"
                >
                    {{ profile.name }}
                </button>
            </div>

            <template v-if="chorePlan">
                <BoardSection
                    :layout="currentStatus"
                    class="w-full pt-12 mx-2 overflow-hidden"
                    :board="filteredChores[0]"
                    :users="users"
                    :automations="automations"
                    :filters="filters"
                    :multi-stage="false"
                    @search="onSearch"
                    resource-name="items"
                />
            </template>
            <WelcomeCard v-else message="Loger profiles">
                <section class="flex flex-col items-center pb-12 mx-auto">
                    <img src="../LogerProfile/empty-box.svg" class="opacity-50" />
                    <h4 class="text-lg font-bold text-body-1">There are not profiles created</h4>
                    <p class="max-w-lg my-3">
                        Lorem ipsum dolor sit amet consectetur adipiscing elit maecenas, ligula rutrum scelerisque vulputate netus urna dui facilisi, habitant auctor sapien a porta nisi arcu. Imperdiet curabitur accumsan torquent fusce mauris blandit fames mollis primis lacinia felis, interdum p.
                    </p>
                    <LogerButton variant="inverse">
                        Add shopping list
                    </LogerButton>
                </section>
            </WelcomeCard>
        </main>
    </AppLayout>
</template>
