
<script lang="ts" setup>
    import { useForm } from '@inertiajs/vue3'
    import { ref } from "vue"
    import { AtField, AtInputPassword } from "atmosphere-ui";

    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'

    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const password = ref();
    const currentPassword  = ref();
    const updatePassword = () => {
        form.put(route('user-password.update'), {
            errorBag: 'updatePassword',
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: () => {
                if (form.errors.password) {
                    form.reset('password', 'password_confirmation')
                    password.value?.focus?.()
                }

                if (form.errors.current_password) {
                    form.reset('current_password')
                    currentPassword.value?.focus?.()
                }
            }
        })
    }

    // Password fields mirror the login screen: AtInputPassword adds the eye
    // reveal toggle. These classes match LogerInput so they blend with the
    // other settings inputs on the page.
    const inputClass = "items-center px-2 rounded-sm bg-base-lvl-2/80 text-body border-base hover:ring-primary block w-full mt-1";
</script>

<template>
    <JetFormSection @submitted="updatePassword"
        :description="$t('Use a long password you do not use anywhere else.')"
    >
        <template #form>
            <AtField class="col-span-6 sm:col-span-2"
                field="current_password"
                :label="$t('Current Password')"
                :errors="form.errors"
            >
                <AtInputPassword id="current_password" :class="inputClass" v-model="form.current_password" ref="currentPassword" autocomplete="current-password" />
            </AtField>

            <AtField class="col-span-6 sm:col-span-2"
                field="password"
                :label="$t('New Password')"
                :errors="form.errors"
            >
                <AtInputPassword id="password" :class="inputClass" v-model="form.password" ref="password" autocomplete="new-password" />
            </AtField>

            <AtField class="col-span-6 sm:col-span-2"
                :label="$t('Confirm password')"
                field="password_confirmation"
                :errors="form.errors"
            >
                <AtInputPassword id="password_confirmation" :class="inputClass" v-model="form.password_confirmation" autocomplete="new-password" />
            </AtField>
        </template>

        <template #actions>
            <jetActionMessage :on="form.recentlySuccessful" class="mr-3">
                {{ $t('Saved') }}
            </jetActionMessage>

            <LogerButton :processing="form.processing" >
                {{ $t('Save') }}
            </LogerButton>
        </template>
    </JetFormSection>
</template>
