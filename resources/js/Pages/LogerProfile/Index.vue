<script lang="ts" setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import WelcomeCard from '@/Components/organisms/WelcomeCard.vue';
import ProfileSectionNav from '@/Components/templates/ProfileSectionNav.vue';
import LogerProfileModal from '@/Components/LogerProfileModal.vue';

defineProps<{
    profiles: Record<string, string>[];
}>();

const isModalOpen = ref(false);
const resourceToEdit = ref({});

const onSaved = () => {
    router.reload();
};
</script>

<template>
    <AppLayout title="Loger Profiles">
        <template #header>
            <ProfileSectionNav :loger-profile="profiles" @action="isModalOpen = true" />
        </template>

        <main class="px-5 mx-auto mt-12 max-w-screen-2xl sm:px-6 lg:px-8 md:pr-16">
            <div v-if="profiles.length" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-body text-lg">Profiles</h2>
                    <LogerButton variant="inverse" @click="isModalOpen = true">
                        Add Profile
                    </LogerButton>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <Link
                        v-for="profile in profiles"
                        :key="profile.id"
                        :href="`/loger-profiles/${profile.id}`"
                        class="block bg-base-lvl-3 border border-base rounded-lg px-4 py-5 text-center hover:border-primary transition-colors cursor-pointer"
                    >
                        <div class="w-12 h-12 mx-auto rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold mb-3">
                            {{ profile.name?.charAt(0)?.toUpperCase() }}
                        </div>
                        <h3 class="font-semibold text-body capitalize truncate">{{ profile.name }}</h3>
                    </Link>
                </div>
            </div>

            <WelcomeCard v-else message="Loger Profiles">
                <section class="flex flex-col items-center pb-12 mx-auto">
                    <img src="./empty-box.svg" class="opacity-50" />
                    <h4 class="text-lg font-bold text-body-1">There are no profiles created</h4>
                    <p class="max-w-lg my-3 text-center text-body-2">
                        Create a profile to track relationships, chores, and linked entities in one place.
                    </p>
                    <LogerButton variant="inverse" @click="isModalOpen = true">
                        Add Loger profile
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
