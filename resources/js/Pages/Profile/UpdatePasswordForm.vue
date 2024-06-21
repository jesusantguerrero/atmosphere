
<script lang="ts" setup>
    import { useForm } from '@inertiajs/vue3'
    import { ref } from "vue"
    import { AtField } from "atmosphere-ui";

    import JetActionMessage from '@/Components/atoms/ActionMessage.vue'
    import LogerButton from '@/Components/atoms/LogerButton.vue'
    import JetFormSection from '@/Components/atoms/FormSection.vue'
    import LogerInput from '@/Components/atoms/LogerInput.vue'


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
                    password.value.focus()
                }

                if (form.errors.current_password) {
                    form.reset('current_password')
                    currentPassword.value.focus()
                }
            }
        })
    }
</script>

<template>
    <JetFormSection @submitted="updatePassword"
        title="Update password"
        description="Ensure your account is using a long, random password to stay secure."
    >
        <template #form>
            <AtField class="col-span-6 sm:col-span-4"
                field="current_password"
                label="Current Password"
                :errors="form.errors"
            >
                <LogerInput id="current_password" type="password" class="block w-full mt-1" v-model="form.current_password" ref="currentPassword" autocomplete="current-password" />
            </AtField>

            <AtField class="col-span-6 sm:col-span-4"
                field="password"
                label="New Password"
                :errors="form.errors"
            >
                <LogerInput id="password" type="password" class="block w-full mt-1" v-model="form.password" ref="password" autocomplete="new-password" />
            </AtField>

            <AtField class="col-span-6 sm:col-span-4"
                label="Confirm password"
                field="password_confirmation"
                :errors="form.errors"
            >
                <LogerInput id="password_confirmation" type="password" class="block w-full mt-1" v-model="form.password_confirmation" autocomplete="new-password" />
            </AtField>
        </template>

        <template #actions>
            <jetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </jetActionMessage>

            <LogerButton :processing="form.processing" >
                Save
            </LogerButton>
        </template>
    </JetFormSection>
</template>

