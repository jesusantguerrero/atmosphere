<script lang="ts" setup>
    import { Link, router, usePage } from '@inertiajs/vue3'
    import { computed, ref } from 'vue'

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import SettingsDisclosure from './SettingsDisclosure.vue'
    import SettingsNotice from './SettingsNotice.vue'
    import SettingsTile from './SettingsTile.vue'
    import UpdateProfileInformationForm from './UpdateProfileInformationForm.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    interface Household {
        id: number,
        name: string,
        owner: string | null,
        role: string | null,
        current: boolean,
    }

    interface Invitation {
        id: number,
        team: string | null,
        owner: string | null,
        role: string | null,
    }

    const props = defineProps<{
        households: Household[],
        invitations: Invitation[],
    }>();

    // Account page: who you are and where you belong first (pending invitations,
    // households and your role in each); editing name, email and photo is folded away.
    const user = computed(() => usePage().props.auth.user);
    const showProfile = ref(false);
    const answering = ref<number | null>(null);

    const roleLabels: Record<string, string> = {
        owner: 'Owner',
        admin: 'Administrator',
        editor: 'Editor',
        guest: 'Guest',
    };
    const roleLabel = (role: string | null) => roleLabels[role ?? ''] ?? role ?? '';

    const currentHousehold = computed(() => props.households.find((household) => household.current));

    const accept = (invitation: Invitation) => {
        answering.value = invitation.id;
        router.patch(route('api.team-invitations.accept-internal', invitation.id), {}, {
            onFinish: () => answering.value = null,
        });
    };

    const reject = (invitation: Invitation) => {
        answering.value = invitation.id;
        router.delete(route('api.team-invitations.reject', invitation.id), {
            preserveScroll: true,
            onFinish: () => answering.value = null,
        });
    };

    const switchTo = (household: Household) => {
        router.put(route('current-team.update'), { team_id: household.id });
    };
</script>

<template>
    <AppLayout :title="$t('Account')">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-5xl px-4 pt-24 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="flex flex-wrap items-center gap-4 mb-6">
                <img
                    :src="user.profile_photo_url"
                    :alt="user.name"
                    class="object-cover w-16 h-16 rounded-full bg-base-lvl-2"
                >
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold truncate text-body">{{ user.name }}</h1>
                    <p class="text-sm truncate text-body-1/70">{{ user.email }}</p>
                </div>
                <LogerButton type="button" variant="ghost" @click="showProfile = true">{{ $t('Edit profile') }}</LogerButton>
            </header>

            <div class="flex flex-col gap-6">
                <SettingsNotice
                    v-for="invitation in invitations"
                    :key="invitation.id"
                    tone="primary"
                    :title="$t('{owner} invited you to {team} as {role}', { owner: invitation.owner, team: invitation.team, role: $t(roleLabel(invitation.role)).toLowerCase() })"
                    :description="currentHousehold?.role === 'guest' ? $t('Today you only see what is shared with you. Accept to use the whole household.') : ''"
                >
                    <template #icon><IMdiEmailOutline /></template>
                    <LogerButton type="button" variant="neutral" :disabled="answering === invitation.id" @click="reject(invitation)">{{ $t('Decline') }}</LogerButton>
                    <LogerButton type="button" :processing="answering === invitation.id" @click="accept(invitation)">{{ $t('Accept') }}</LogerButton>
                </SettingsNotice>

                <div class="grid gap-4 sm:grid-cols-3">
                    <SettingsTile
                        :label="$t('Email')"
                        :value="user.email_verified_at ? $t('Verified email') : $t('Not verified')"
                        :tone="user.email_verified_at ? 'ok' : 'warn'"
                        :hint="$t('Alerts and invitations arrive here')"
                    >
                        <template #icon><IMdiEmailOutline /></template>
                    </SettingsTile>
                    <SettingsTile :label="$t('Language')" :value="user.language === 'es' ? 'Español' : 'English'">
                        <template #icon><IMdiTranslate /></template>
                        <LogerButton type="button" variant="ghost" class="!px-3 !py-1 text-sm" @click="showProfile = true">{{ $t('Change') }}</LogerButton>
                    </SettingsTile>
                    <SettingsTile
                        :label="$t('Current household')"
                        :value="currentHousehold?.name ?? '—'"
                        :hint="currentHousehold?.role === 'guest' ? $t('Guest access: you see what is shared') : $t(roleLabel(currentHousehold?.role ?? null))"
                    >
                        <template #icon><IMdiHomeOutline /></template>
                    </SettingsTile>
                </div>

                <section class="border rounded-xl border-base bg-base-lvl-3">
                    <header class="flex flex-wrap items-center justify-between gap-2 px-5 py-4 border-b border-base">
                        <h2 class="font-bold text-body">{{ $t('Your households') }}</h2>
                        <Link :href="route('teams.create')" class="text-sm font-bold text-primary-ink hover:underline">
                            {{ $t('Start my own household') }}
                        </Link>
                    </header>
                    <ul class="divide-y divide-base">
                        <li v-for="household in households" :key="household.id" class="flex flex-wrap items-center gap-3 px-5 py-3">
                            <span class="flex items-center justify-center w-10 h-10 font-bold rounded-lg bg-base-lvl-2 text-body">
                                {{ household.name.charAt(0).toUpperCase() }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold truncate text-body">{{ household.name }}</p>
                                <p v-if="household.role !== 'owner' && household.owner" class="text-sm text-body-1/70">
                                    {{ $t('Owned by {name}', { name: household.owner }) }}
                                </p>
                            </div>
                            <span
                                class="px-2.5 py-1 text-xs font-bold rounded-full"
                                :class="household.role === 'guest' ? 'text-warning bg-warning/10' : 'text-body-1 bg-base-lvl-2'"
                            >
                                {{ $t(roleLabel(household.role)) }}
                            </span>
                            <span v-if="household.current" class="px-2.5 py-1 text-xs font-bold rounded-full text-success bg-success/10">
                                {{ $t('Current') }}
                            </span>
                            <button
                                v-else
                                type="button"
                                class="px-3 py-1.5 text-sm font-semibold rounded-md text-primary-ink hover:bg-primary/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                                @click="switchTo(household)"
                            >
                                {{ $t('Switch') }}
                            </button>
                        </li>
                    </ul>
                </section>

                <SettingsDisclosure
                    v-if="$page.props.jetstream.canUpdateProfileInformation"
                    v-model:open="showProfile"
                    :title="$t('Name, email, photo and language')"
                    :hint="$t('Opens right here')"
                >
                    <template #icon><IMdiAccountOutline /></template>
                    <UpdateProfileInformationForm :user="user" />
                </SettingsDisclosure>
            </div>
        </div>
    </AppLayout>
</template>
