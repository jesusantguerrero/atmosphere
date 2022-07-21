<template>
    <jet-action-section
        title="Two Factor Authentication"
        description="Add additional security to your account using two factor authentication.">
        <template #content>
            <h3 class="text-lg font-medium" >
                {{ titleLabel }}
            </h3>

            <div class="mt-3 max-w-xl text-sm text-base-400">
                <p>
                    When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application.
                </p>
            </div>

            <div v-if="twoFactorEnabled">
                <div v-if="qrCode">
                    <div class="mt-4 max-w-xl text-sm text-gray-600">
                        <p class="font-semibold">
                            Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application.
                        </p>
                    </div>

                    <div class="mt-4 dark:p-4 dark:w-56 dark:bg-base-600" v-html="qrCode">
                    </div>
                </div>

                <div v-if="recoveryCodes.length > 0">
                    <div class="mt-4 max-w-xl text-sm text-gray-600">
                        <p class="font-semibold">
                            Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.
                        </p>
                    </div>

                    <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 rounded-lg">
                        <div v-for="code in recoveryCodes" :key="code">
                            {{ code }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <div v-if="! twoFactorEnabled">
                    <jet-confirms-password @confirmed="enableTwoFactorAuthentication">
                        <jet-button type="button" :class="{ 'opacity-25': enabling }" :disabled="enabling">
                            Enable
                        </jet-button>
                    </jet-confirms-password>
                </div>

                <div v-else>
                    <jet-confirms-password @confirmed="regenerateRecoveryCodes">
                        <jet-secondary-button class="mr-3"
                                        v-if="recoveryCodes.length > 0">
                            Regenerate Recovery Codes
                        </jet-secondary-button>
                    </jet-confirms-password>

                    <jet-confirms-password @confirmed="showRecoveryCodes">
                        <jet-secondary-button class="mr-3" v-if="recoveryCodes.length === 0">
                            Show Recovery Codes
                        </jet-secondary-button>
                    </jet-confirms-password>

                    <jet-confirms-password @confirmed="disableTwoFactorAuthentication">
                        <jet-danger-button
                                        :class="{ 'opacity-25': disabling }"
                                        :disabled="disabling">
                            Disable
                        </jet-danger-button>
                    </jet-confirms-password>
                </div>
            </div>
        </template>
    </jet-action-section>
</template>

<script setup>
    import JetActionSection from '@/Jetstream/ActionSection.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetConfirmsPassword from '@/Jetstream/ConfirmsPassword.vue'
    import JetDangerButton from '@/Jetstream/DangerButton.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import { Inertia } from '@inertiajs/inertia'
    import { usePage } from '@inertiajs/inertia-vue3'
    import { computed, reactive } from 'vue'

    const state = reactive({
            enabling: false,
            disabling: false,

            qrCode: null,
            recoveryCodes: [],
    });

    const enableTwoFactorAuthentication = () => {
        state.enabling = true

        Inertia.post('/user/two-factor-authentication', {}, {
            preserveScroll: true,
            onSuccess: () => Promise.all([
                showQrCode(),
                showRecoveryCodes(),
            ]),
            onFinish: () => (state.enabling = false),
        })
    }

    const showQrCode = () => {
        return axios.get('/user/two-factor-qr-code')
        .then(response => {
            state.qrCode = response.data.svg
        })
    }

    const showRecoveryCodes = () => {
        return axios.get('/user/two-factor-recovery-codes')
            .then(response => {
                state.recoveryCodes = response.data
            })
    }

    const regenerateRecoveryCodes = () => {
    axios.post('/user/two-factor-recovery-codes')
            .then(response => {
                showRecoveryCodes()
            })
    }

    const disableTwoFactorAuthentication = () => {
        state.disabling = true

        Inertia.delete('/user/two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => (state.disabling = false),
        })
    }

    const pageProps = usePage().props;
    const twoFactorEnabled = computed(() => {
        return ! state.enabling && pageProps.value.user.two_factor_enabled
    })
    const titleLabel = computed(() => {
        return twoFactorEnabled.value ? "You have enabled two factor authentication." : " You have not enabled two factor authentication."
    })
</script>
