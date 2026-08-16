<script lang="ts" setup>
    import { useForm, usePage } from '@inertiajs/vue3'
    import { computed } from 'vue'

    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import JetCheckbox from '@/Components/atoms/Checkbox.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'

    interface NotificationPrefs {
        email: boolean;
        push: boolean;
    }

    const prefs = computed<NotificationPrefs>(() => usePage().props.notificationPrefs ?? { email: true, push: true })

    const form = useForm({
        email: Boolean(prefs.value.email),
        push: Boolean(prefs.value.push),
    })

    const channels = [
        { key: 'email' as const, label: 'Email', hint: 'Receive an email for alerts like imported transactions, planned bills and watchlist limits.' },
        { key: 'push' as const, label: 'Push notifications', hint: 'Get a push on your devices (requires enabling notifications in the browser).' },
    ]

    const updatePrefs = () => {
        form.patch(route('user.notification-prefs'), {
            preserveScroll: true,
        })
    }
</script>

<template>
    <JetFormSection
        :title="$t('Notifications')"
        :description="$t('Choose how Loger reaches you. In-app notifications are always on.')"
        @submitted="updatePrefs"
    >
        <template #form>
            <div class="col-span-6 space-y-3">
                <label
                    v-for="channel in channels"
                    :key="channel.key"
                    class="flex items-center justify-between px-4 py-3 border rounded-md cursor-pointer border-base-lvl-3 hover:bg-base-lvl-2"
                >
                    <span class="pr-4">
                        <span class="block font-medium text-body">{{ $t(channel.label) }}</span>
                        <span class="block text-sm text-body-1/60">{{ $t(channel.hint) }}</span>
                    </span>
                    <JetCheckbox v-model:checked="form[channel.key]" />
                </label>
            </div>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                {{ $t('Saved') }}
            </JetActionMessage>

            <LogerButton :processing="form.processing">
                {{ $t('Save') }}
            </LogerButton>
        </template>
    </JetFormSection>
</template>
