<script lang="ts" setup>
    import { useForm, usePage } from '@inertiajs/vue3'
    import { computed, onMounted, ref } from 'vue'

    import AppLayout from '@/Components/templates/AppLayout.vue'
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import SettingsNotice from './SettingsNotice.vue'
    import SettingsTile from './SettingsTile.vue'
    import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue'

    interface ModuleProp {
        id: number;
        name: string;
        alias: string;
        enabled: boolean;
    }

    const props = defineProps<{
        notificationPrefs: { email: boolean, push: boolean },
    }>();

    // Preferences page: how Loger reaches you (and whether this browser can),
    // then which modules show in your menu. Every switch saves on its own.
    const page = usePage();
    const pageModules = computed<ModuleProp[]>(() => page.props.modules ?? []);

    const notifications = useForm({
        email: Boolean(props.notificationPrefs.email),
        push: Boolean(props.notificationPrefs.push),
    });
    const saveNotifications = () => notifications.patch(route('user.notification-prefs'), { preserveScroll: true });

    const modules = useForm({
        modules: pageModules.value.map((module) => ({ name: module.name, enabled: Boolean(module.enabled) })),
    });
    const saveModules = () => modules.patch(route('user.modules.update'), { preserveScroll: true });

    const browserPermission = ref<NotificationPermission | 'unsupported'>('unsupported');
    onMounted(() => {
        browserPermission.value = 'Notification' in window ? Notification.permission : 'unsupported';
    });

    const allowPush = async () => {
        const deferred = (window as any).OneSignalDeferred;
        if (deferred) {
            deferred.push(async (OneSignal: any) => {
                await OneSignal.Notifications.requestPermission();
                browserPermission.value = Notification.permission;
            });
            return;
        }
        browserPermission.value = await Notification.requestPermission();
    };
</script>

<template>
    <AppLayout :title="$t('Preferences')">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="max-w-5xl px-4 pt-24 pb-20 mx-auto sm:px-6 lg:px-8">
            <header class="mb-6">
                <h1 class="text-2xl font-bold text-body">{{ $t('Preferences') }}</h1>
                <p class="mt-1 text-sm text-body-1/70">{{ $t('How Loger lets you know and what you see in the menu.') }}</p>
            </header>

            <div class="flex flex-col gap-6">
                <SettingsNotice
                    v-if="notifications.push && browserPermission === 'default'"
                    :title="$t('This browser cannot send you alerts yet')"
                    :description="$t('Allow them to hear about bills due and imported purchases without opening Loger.')"
                >
                    <template #icon><IMdiBellOutline /></template>
                    <LogerButton type="button" @click="allowPush">{{ $t('Allow alerts') }}</LogerButton>
                </SettingsNotice>
                <SettingsNotice
                    v-else-if="notifications.push && browserPermission === 'denied'"
                    :title="$t('Alerts are blocked in this browser')"
                    :description="$t('Allow notifications for this site in your browser settings to get them here.')"
                >
                    <template #icon><IMdiBellOffOutline /></template>
                </SettingsNotice>

                <section class="flex flex-col gap-3">
                    <h2 class="font-bold text-body">{{ $t('Alerts') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <SettingsTile
                            :label="$t('Email')"
                            :value="notifications.email ? $t('On') : $t('Off')"
                            :tone="notifications.email ? 'ok' : 'neutral'"
                        >
                            <template #icon><IMdiEmailOutline /></template>
                            <label class="flex items-center justify-between w-full gap-3 cursor-pointer min-h-11">
                                <span class="text-sm text-body-1/70">{{ $t('Imported purchases, bills and limits') }}</span>
                                <input v-model="notifications.email" type="checkbox" class="w-5 h-5 rounded text-primary focus:ring-primary" @change="saveNotifications">
                            </label>
                        </SettingsTile>
                        <SettingsTile
                            :label="$t('Push')"
                            :value="!notifications.push ? $t('Off') : browserPermission === 'granted' ? $t('On') : $t('Not allowed here')"
                            :tone="!notifications.push ? 'neutral' : browserPermission === 'granted' ? 'ok' : 'warn'"
                        >
                            <template #icon><IMdiCellphone /></template>
                            <label class="flex items-center justify-between w-full gap-3 cursor-pointer min-h-11">
                                <span class="text-sm text-body-1/70">{{ $t('On your devices') }}</span>
                                <input v-model="notifications.push" type="checkbox" class="w-5 h-5 rounded text-primary focus:ring-primary" @change="saveNotifications">
                            </label>
                        </SettingsTile>
                        <SettingsTile :label="$t('In the app')" :value="$t('Always')" tone="ok" :hint="$t('The bell at the top')">
                            <template #icon><IMdiBellOutline /></template>
                        </SettingsTile>
                    </div>
                    <JetActionMessage :on="notifications.recentlySuccessful" class="self-end">{{ $t('Saved') }}</JetActionMessage>
                </section>

                <section class="flex flex-col gap-4 p-5 border rounded-xl border-base bg-base-lvl-3">
                    <header>
                        <h2 class="font-bold text-body">{{ $t('What you see in the menu') }}</h2>
                        <p class="text-sm text-body-1/70">{{ $t('Turn off what you do not use. Nothing is deleted: it only hides from your menu.') }}</p>
                    </header>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label
                            v-for="(module, index) in modules.modules"
                            :key="module.name"
                            class="flex items-center gap-3 px-4 border cursor-pointer min-h-14 rounded-xl"
                            :class="module.enabled ? 'border-primary/50 bg-primary/5' : 'border-base'"
                        >
                            <input v-model="modules.modules[index].enabled" type="checkbox" class="w-5 h-5 rounded text-primary focus:ring-primary" @change="saveModules">
                            <span class="font-bold" :class="module.enabled ? 'text-body' : 'text-body-1/70'">
                                {{ $t(pageModules[index]?.alias ?? module.name) }}
                            </span>
                        </label>
                    </div>
                    <JetActionMessage :on="modules.recentlySuccessful" class="self-end">{{ $t('Saved') }}</JetActionMessage>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
