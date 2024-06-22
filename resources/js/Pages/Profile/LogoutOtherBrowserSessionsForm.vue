<script lang="ts" setup>
    import { useForm } from '@inertiajs/vue3'
    import { ref } from "vue";

    import ActionMessage from '@/Components/atoms/ActionMessage.vue'
    import ActionSection from '@/Components/atoms/ActionSection.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import LogerInput from '@/Components/atoms/LogerInput.vue'
    import Button from '@/Components/atoms/Button.vue'
    import DialogModal from '@/Components/atoms/DialogModal.vue'
    import Input from '@/Components/atoms/Input.vue'
    import InputError from '@/Components/atoms/InputError.vue'
    import SecondaryButton from '@/Components/atoms/SecondaryButton.vue'


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
        title="Browser Sessions"
        description="Manage and log out your active sessions on other browsers and devices.">

        <template #content>
            <header class="max-w-xl text-sm">
                If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.
            </header>

            <!-- Other Browser Sessions -->
            <section class="mt-5 space-y-6" v-if="sessions.length > 0">
                <div class="flex items-center" v-for="(session, i) in sessions" :key="i">
                    <div>
                        <svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor" class="w-8 h-8 text-gray-500" v-if="session.agent.is_desktop">
                            <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-gray-500" v-else>
                            <path d="M0 0h24v24H0z" stroke="none"></path><rect x="7" y="4" width="10" height="16" rx="1"></rect><path d="M11 5h2M12 17v.01"></path>
                        </svg>
                    </div>

                    <div class="ml-3">
                        <div class="text-sm text-gray-600">
                            {{ session.agent.platform }} - {{ session.agent.browser }}
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">
                                {{ session.ip_address }},

                                <span class="font-semibold text-green-500" v-if="session.is_current_device">This device</span>
                                <span v-else>Last active {{ session.last_active }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex items-center mt-5">
                <LogerButton @click="confirmLogout" variant="neutral">
                    Log Out Other Browser Sessions
                </LogerButton>

                <ActionMessage :on="form.recentlySuccessful" class="ml-3">
                    Done.
                </ActionMessage>
            </div>

            <!-- Log Out Other Devices Confirmation Modal -->
            <DialogModal :show="confirmingLogout" @close="closeModal">
                <template #title>
                    Log Out Other Browser Sessions
                </template>

                <template #content>
                    Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.

                    <div class="mt-4">
                        <LogerInput type="password" class="block w-3/4 mt-1"
                            placeholder="Password"
                            ref="password"
                            v-model="form.password"
                            @keyup.enter="logoutOtherBrowserSessions"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <LogerButton @click="closeModal" variant="neutral" class="inline-flex">
                        Cancel
                    </LogerButton>

                    <LogerButton class="ml-2 inline-flex" @click="logoutOtherBrowserSessions" :processing="form.processing" :disabled="form.processing">
                        Log Out Other Browser Sessions
                    </LogerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
