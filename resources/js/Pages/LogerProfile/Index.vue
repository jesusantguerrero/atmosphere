<script lang="ts" setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import HouseSectionNav from '@/Components/templates/HouseSectionNav.vue';
import LogerProfileModal from '@/Components/LogerProfileModal.vue';

defineProps<{
    profiles: Record<string, string>[];
}>();

const isModalOpen = ref(false);
const resourceToEdit = ref({});

// Deterministic per-profile avatar color, drawn from the app's chart palette
const AVATAR_PALETTE = [
    '#7B77D1', // purple
    '#F37EA1', // pink
    '#80CDFE', // blue
    '#6EE7B7', // teal-green
    '#FBBF77', // amber
    '#A78BFA', // violet
    '#5EEAD4', // teal
    '#F59E9E', // coral
];

const avatarStyle = (profile: Record<string, any>) => {
    const seed = String(profile.id ?? profile.name ?? '');
    let hash = 0;
    for (let i = 0; i < seed.length; i++) {
        hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    }
    const color = AVATAR_PALETTE[hash % AVATAR_PALETTE.length];
    return { backgroundColor: `${color}1F`, color };
};

const onSaved = () => {
    router.reload();
};
</script>

<template>
    <AppLayout :title="$t('Profiles')">
        <template #header>
            <HouseSectionNav>
                <template #actions>
                    <LogerButton variant="inverse" @click="isModalOpen = true">
                        {{ $t('Add profile') }}
                    </LogerButton>
                </template>
            </HouseSectionNav>
        </template>

        <main class="px-5 mx-auto pt-16 max-w-screen-2xl sm:px-6 lg:px-8 md:pr-16">
            <div v-if="profiles.length" class="space-y-4">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link
                        v-for="profile in profiles"
                        :key="profile.id"
                        :href="`/loger-profiles/${profile.id}`"
                        class="block bg-base-lvl-3 border border-base rounded-lg px-4 py-5 text-center hover:border-primary transition-colors cursor-pointer"
                    >
                        <div
                            class="w-12 h-12 mx-auto rounded-full flex items-center justify-center text-xl font-bold mb-3"
                            :style="avatarStyle(profile)"
                        >
                            {{ profile.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <h3 class="font-semibold text-body capitalize truncate">{{ profile.name }}</h3>
                    </Link>
                </div>
            </div>

            <WelcomeCard v-else :message="$t('Profiles')">
                <section class="flex flex-col items-center max-w-xl py-12 mx-auto text-center">
                    <div class="flex items-center justify-center w-16 h-16 mb-5 rounded-full bg-primary/10 text-primary">
                        <i class="text-2xl fa fa-users"></i>
                    </div>
                    <h4 class="text-xl font-bold text-body">{{ $t('Welcome to Family') }}</h4>
                    <p class="max-w-md my-3 text-center text-body-2">
                        {{ $t('Add a profile for each person and see everything linked to them in one place.') }}
                    </p>
                    <LogerButton variant="inverse" @click="isModalOpen = true">
                        {{ $t('Add profile') }}
                    </LogerButton>
                </section>
            </WelcomeCard>
        </main>

        <LogerProfileModal
            v-if="isModalOpen"
            v-model:show="isModalOpen"
            :form-data="resourceToEdit"
            @saved="onSaved"
        />
    </AppLayout>
</template>
