<script lang="ts" setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';
import LogerInput from '@/Components/atoms/LogerInput.vue';
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

const colorFor = (seedRaw: string) => {
    const seed = String(seedRaw ?? '');
    let hash = 0;
    for (let i = 0; i < seed.length; i++) {
        hash = (hash * 31 + seed.charCodeAt(i)) >>> 0;
    }
    return AVATAR_PALETTE[hash % AVATAR_PALETTE.length];
};

const avatarStyle = (profile: Record<string, any>) => {
    const color = colorFor(profile.id ?? profile.name ?? '');
    return { backgroundColor: `${color}1F`, color };
};

const onSaved = () => {
    router.reload();
};

// --- Household quick-add (empty state) -----------------------------------
// Add everyone at once instead of one-modal-at-a-time. The owner's name is
// seeded so the first row is already useful; empty rows are ignored on save.
const ownerName = (usePage().props.auth?.user?.name as string) ?? '';
const rows = ref<{ name: string }[]>([
    { name: ownerName },
    { name: '' },
    { name: '' },
]);
const saving = ref(false);

const addRow = () => rows.value.push({ name: '' });
const removeRow = (index: number) => {
    if (rows.value.length > 1) rows.value.splice(index, 1);
};

const rowStyle = (name: string) => {
    const color = colorFor(name || '?');
    return { backgroundColor: `${color}1F`, color };
};

const createAll = async () => {
    const names = rows.value.map((r) => r.name.trim()).filter(Boolean);
    if (!names.length) return;
    saving.value = true;
    try {
        for (const name of names) {
            await axios.post('/loger-profiles/', { name });
        }
        router.reload({ only: ['profiles'] });
    } finally {
        saving.value = false;
    }
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
                <section class="flex flex-col items-center w-full max-w-lg py-12 mx-auto text-center">
                    <div class="flex items-center justify-center w-16 h-16 mb-5 rounded-full bg-primary/10 text-primary">
                        <i class="text-2xl fa fa-users"></i>
                    </div>
                    <h4 class="text-xl font-bold text-body">{{ $t('Who lives in your household?') }}</h4>
                    <p class="max-w-md my-3 text-center text-body-2">
                        {{ $t('Add everyone now — you can always add more later.') }}
                    </p>

                    <div class="w-full mt-4 space-y-2 text-left">
                        <div
                            v-for="(row, index) in rows"
                            :key="index"
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex items-center justify-center w-10 h-10 text-sm font-bold rounded-full shrink-0"
                                :style="rowStyle(row.name)"
                            >
                                {{ (row.name?.charAt(0) || '?').toUpperCase() }}
                            </div>
                            <LogerInput
                                v-model="row.name"
                                class="flex-1"
                                :placeholder="$t('Name')"
                                @keydown.enter="addRow"
                            />
                            <button
                                type="button"
                                class="w-8 h-8 rounded-md text-body-1/40 hover:text-error shrink-0"
                                :class="{ 'invisible': rows.length <= 1 }"
                                @click="removeRow(index)"
                            >
                                <i class="fa fa-times" />
                            </button>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center gap-1 mt-1 text-sm font-semibold text-primary hover:text-primary/80"
                            @click="addRow"
                        >
                            <i class="text-xs fa fa-plus" /> {{ $t('Add another') }}
                        </button>
                    </div>

                    <div class="flex flex-col items-center w-full gap-2 mt-6">
                        <LogerButton
                            variant="inverse"
                            class="w-full"
                            :processing="saving"
                            :disabled="saving"
                            @click="createAll"
                        >
                            {{ $t('Create profiles') }}
                        </LogerButton>
                        <button
                            type="button"
                            class="text-xs underline text-body-1/50 hover:text-body-1"
                            @click="isModalOpen = true"
                        >
                            {{ $t('Add one at a time') }}
                        </button>
                    </div>
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
