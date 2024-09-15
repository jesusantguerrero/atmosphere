<script setup lang="ts">
    import { router, usePage, useForm } from '@inertiajs/vue3'
    import { computed, reactive, watch } from 'vue'

    import ActionSection from '@/Components/atoms/ActionSection.vue'
    import LogerButton from "@/Components/atoms/LogerButton.vue";
    import JetConfirmsPassword from '@/Components/atoms/ConfirmsPassword.vue'


    const props = defineProps<{
        requiresConfirmation: boolean,
    }>();

    const state = reactive({
        enabling: false,
        confirming: false,
        disabling: false,
        qrCode: null,
        setupKey: null,
        recoveryCodes: [],
    });

    const confirmationForm = useForm({
        code: '',
    });

    const enableTwoFactorAuthentication = () => {
        state.enabling = true

        router.post('/user/two-factor-authentication', {}, {
            preserveScroll: true,
            onSuccess: () => Promise.all([
                showQrCode(),
                showSetupKey(),
                showRecoveryCodes(),
            ]),
            onFinish: () => {
                state.enabling = false
                state.confirming = props.requiresConfirmation;
            },
        })
    }

    const showQrCode = () => {
        return axios.get('/user/two-factor-qr-code')
        .then(response => {
            state.qrCode = response.data.svg
        })
    }

    const showSetupKey = () => {
        return axios.get(route('two-factor.secret-key')).then(response => {
            state.setupKey = response.data.secretKey;
        });
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

    const confirmTwoFactorAuthentication = () => {
        confirmationForm.post(route('two-factor.confirm'), {
            errorBag: "confirmTwoFactorAuthentication",
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                state.confirming = false;
                state.qrCode = null;
                state.setupKey = null;
            },
        });
    };

    const disableTwoFactorAuthentication = () => {
        state.disabling = true

        router.delete('/user/two-factor-authentication', {
            preserveScroll: true,
            onSuccess: () => (state.disabling = false),
        })
    }

    const page = usePage();

    const isTwoFactorEnabled = computed(() => {
        return !state.enabling && page.props.auth.user.two_factor_enabled
    })

    watch(isTwoFactorEnabled, () => {
        if (!isTwoFactorEnabled.value) {
            confirmationForm.reset();
            confirmationForm.clearErrors();
        }
    });
    const titleLabel = computed(() => {
        return isTwoFactorEnabled.value ? "You have enabled two factor authentication." : " You have not enabled two factor authentication."
    })
</script>


<template>
    <ActionSection
        title="Two Factor Authentication"
        description="Add additional security to your account using two factor authentication."

    >
        <template #content>
            <h3 v-if="isTwoFactorEnabled && state.confirming" class="text-lg font-medium text-gray-900">
                Finish enabling two factor authentication.
            </h3>
            <h3 class="text-lg font-medium" v-else>
                {{ titleLabel }}
            </h3>

            <div class="max-w-xl mt-3 text-sm text-body-1">
                <p>
                    When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone's Google Authenticator application.
                </p>
            </div>

            <div v-if="isTwoFactorEnabled">
                <section v-if="state.qrCode">
                    <div class="max-w-xl mt-4 text-sm text-gray-600">
                        <p v-if="confirming" class="font-semibold">
                            To finish enabling two factor authentication, scan the following QR code using your phone's authenticator application or enter the setup key and provide the generated OTP code.
                        </p>
                        <p v-else class="font-semibold">
                            Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application.
                        </p>
                    </div>

                    <div class="mt-4 dark:p-4 dark:w-56 dark:bg-base-lvl-1" v-html="state.qrCode" />

                    <div v-if="confirming" class="mt-4">
                        <AtField field="code" label="Code"
                            :errors="confirmationForm.errors"
                        >
                            <LogerInput
                                id="code"
                                v-model="confirmationForm.code"
                                type="text"
                                name="code"
                                class="block w-1/2 mt-1"
                                inputmode="numeric"
                                autofocus
                                autocomplete="one-time-code"
                                @keyup.enter="confirmTwoFactorAuthentication"
                            />
                        </AtField>
                    </div>
                </section>

                <div v-if="state.recoveryCodes.length > 0 && !state.confirming">
                    <div class="max-w-xl mt-4 text-sm text-gray-600">
                        <p class="font-bold">
                            Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.
                        </p>
                    </div>

                    <div class="grid max-w-xl gap-1 px-4 py-4 mt-4 font-mono text-sm bg-gray-100 rounded-lg">
                        <div v-for="code in state.recoveryCodes" :key="code">
                            {{ code }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <section v-if="!isTwoFactorEnabled">
                    <JetConfirmsPassword @confirmed="enableTwoFactorAuthentication">
                        <LogerButton type="button" :processing="state.enabling" variant="secondary">
                            Enable
                        </LogerButton>
                    </JetConfirmsPassword>
                </section>

                <div v-else>
                    <footer class="flex">
                        <JetConfirmsPassword @confirmed="regenerateRecoveryCodes">
                            <LogerButton class="mr-3" variant="neutral"
                                v-if="state.recoveryCodes.length > 0">
                                Regenerate Recovery Codes
                            </LogerButton>
                        </JetConfirmsPassword>

                        <JetConfirmsPassword @confirmed="showRecoveryCodes">
                            <LogerButton class="mr-3" v-if="state.recoveryCodes.length === 0" variant="neutral">
                                Show Recovery Codes
                            </LogerButton>
                        </JetConfirmsPassword>

                        <JetConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                            <LogerButton
                                :processing="state.disabling"
                                variant="error"
                            >
                                Disable
                            </LogerButton>
                        </JetConfirmsPassword>
                    </footer>
                </div>
            </div>
        </template>
    </ActionSection>
</template>
