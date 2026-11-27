<script setup lang="ts">
    import { router, usePage, useForm } from '@inertiajs/vue3'
    import { computed, reactive, watch } from 'vue'

    import ActionSection from '@/Components/atoms/ActionSection.vue'
    import LogerButton from "@/Components/atoms/LogerButton.vue";
    import LogerInput from '@/Components/atoms/LogerInput.vue'
    import { AtField } from 'atmosphere-ui'
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
        return isTwoFactorEnabled.value ? "Two-step verification is on." : "Two-step verification is off."
    })
</script>


<template>
    <ActionSection
        :title="$t('How it works')"
        :description="$t('Besides your password, Loger asks for a 6-digit code from your phone when you sign in on a new device.')"

    >
        <template #content>
            <h3 v-if="isTwoFactorEnabled && state.confirming" class="text-lg font-medium text-body">
                {{ $t('Finish turning on two-step verification.') }}
            </h3>
            <h3 class="text-lg font-medium" v-else>
                {{ $t(titleLabel) }}
            </h3>

            <div class="max-w-xl mt-3 text-sm text-body-1">
                <p>{{ $t('You will need an authenticator app such as Google Authenticator or 1Password.') }}</p>
            </div>

            <div v-if="isTwoFactorEnabled">
                <section v-if="state.qrCode">
                    <div class="max-w-xl mt-4 text-sm text-body-1">
                        <p v-if="state.confirming" class="font-semibold">
                            {{ $t('Scan this code with your authenticator app, then type the code it shows.') }}
                        </p>
                        <p v-else class="font-semibold">
                            {{ $t('Two-step verification is on. Scan this code with your authenticator app.') }}
                        </p>
                    </div>

                    <div class="mt-4 dark:p-4 dark:w-56 dark:bg-base-lvl-1" v-html="state.qrCode" />

                    <div v-if="state.confirming" class="mt-4">
                        <AtField field="code" :label="$t('Code')"
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
                        <LogerButton type="button" class="mt-3" :processing="confirmationForm.processing" @click="confirmTwoFactorAuthentication">
                            {{ $t('Confirm') }}
                        </LogerButton>
                    </div>
                </section>

                <div v-if="state.recoveryCodes.length > 0 && !state.confirming">
                    <div class="max-w-xl mt-4 text-sm text-body-1">
                        <p class="font-bold">
                            {{ $t('Keep these recovery codes in a password manager. They get you back in if you lose your phone.') }}
                        </p>
                    </div>

                    <div class="grid max-w-xl gap-1 px-4 py-4 mt-4 font-mono text-sm bg-base-lvl-1 rounded-lg">
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
                            {{ $t('Turn on') }}
                        </LogerButton>
                    </JetConfirmsPassword>
                </section>

                <div v-else>
                    <footer class="flex">
                        <JetConfirmsPassword @confirmed="regenerateRecoveryCodes">
                            <LogerButton class="mr-3" variant="neutral"
                                v-if="state.recoveryCodes.length > 0">
                                {{ $t('New recovery codes') }}
                            </LogerButton>
                        </JetConfirmsPassword>

                        <JetConfirmsPassword @confirmed="showRecoveryCodes">
                            <LogerButton class="mr-3" v-if="state.recoveryCodes.length === 0" variant="neutral">
                                {{ $t('Show recovery codes') }}
                            </LogerButton>
                        </JetConfirmsPassword>

                        <JetConfirmsPassword @confirmed="disableTwoFactorAuthentication">
                            <LogerButton
                                :processing="state.disabling"
                                variant="error"
                            >
                                {{ $t('Turn off') }}
                            </LogerButton>
                        </JetConfirmsPassword>
                    </footer>
                </div>
            </div>
        </template>
    </ActionSection>
</template>
