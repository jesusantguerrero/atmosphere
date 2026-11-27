<script lang="ts" setup>
    import { useForm } from '@inertiajs/vue3'
    import { ref } from "vue";

    import ActionMessage from '@/Components/atoms/ActionMessage.vue'
    import ActionSection from '@/Components/atoms/ActionSection.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import LogerInput from '@/Components/atoms/LogerInput.vue'
    import DialogModal from '@/Components/atoms/DialogModal.vue'
    import InputError from '@/Components/atoms/InputError.vue'


    defineProps<{
        sessions: any[],
    }>();


    const confirmingLogout = ref(false);
    const form = useForm({
        password: '',
    })

    const password = ref();
    const confirmLogout = () => {
        confirmingLogout.value = true
        setTimeout(() => password.value.focus(), 250)
    }

    const logoutOtherBrowserSessions = () =>  {
        form.delete(route('other-browser-sessions.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => password.value.focus(),
            onFinish: () => form.reset(),
        })
    }

    const closeModal = () => {
        confirmingLogout.value = false

        form.reset()
    }
</script>

<template>
    <ActionSection
        :title="$t('Where you are signed in')"
        :description="$t('If you see a device you do not recognize, sign it out and change your password.')">

        <template #content>
            <ul class="flex flex-col divide-y divide-base" v-if="sessions.length > 0">
                <li class="flex items-center gap-3 py-3 first:pt-0" v-for="(session, i) in sessions" :key="i">
                    <span class="flex items-center justify-center w-10 h-10 text-xl rounded-lg bg-base-lvl-2 text-body-1">
                        <IMdiMonitor v-if="session.agent.is_desktop" />
                        <IMdiCellphone v-else />
                    </span>

                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-body">
                            {{ $t('{browser} on {platform}', { browser: session.agent.browser, platform: session.agent.platform }) }}
                        </p>
                        <p class="text-sm text-body-1/70">
                            {{ session.ip_address }} ·
                            {{ session.is_current_device ? $t('active now') : $t('last active {time}', { time: session.last_active }) }}
                        </p>
                    </div>

                    <span v-if="session.is_current_device" class="px-2.5 py-1 text-xs font-bold rounded-full text-success bg-success/10">
                        {{ $t('This device') }}
                    </span>
                </li>
            </ul>

            <div class="flex items-center mt-4" v-if="sessions.length > 1">
                <LogerButton @click="confirmLogout" variant="ghost">
                    {{ $t('Sign out the others') }}
                </LogerButton>

                <ActionMessage :on="form.recentlySuccessful" class="ml-3">
                    {{ $t('Done') }}
                </ActionMessage>
            </div>

            <!-- Log Out Other Devices Confirmation Modal -->
            <DialogModal :show="confirmingLogout" @close="closeModal">
                <template #title>
                    {{ $t('Sign out the others') }}
                </template>

                <template #content>
                    {{ $t('Enter your password to sign out every other device.') }}

                    <div class="mt-4">
                        <LogerInput type="password" class="block w-3/4 mt-1"
                            :placeholder="$t('Password')"
                            ref="password"
                            v-model="form.password"
                            @keyup.enter="logoutOtherBrowserSessions"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <LogerButton @click="closeModal" variant="neutral" class="inline-flex">
                        {{ $t('Cancel') }}
                    </LogerButton>

                    <LogerButton class="ml-2 inline-flex" @click="logoutOtherBrowserSessions" :processing="form.processing" :disabled="form.processing">
                        {{ $t('Sign out the others') }}
                    </LogerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
