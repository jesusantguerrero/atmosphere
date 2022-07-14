<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            mode="register"
            btn-class="text-white bg-pink-500 rounded-lg hover:bg-pink-600"
            link-class="text-pink-500 hover:text-pink-600"
            v-model:isLoading="form.processing"
            :errors="form.errors"
            @submit="submit"
            @home-pressed="onHomePressed"
            @link-pressed="onLinkPressed"
        >
            <template #prependInput>
                <AtField label="Name">
                    <AtInput v-model="form.name" required />
                </AtField>
            </template>
        </AtAuthForm>
  </AtAuthBox>
</template>

<script setup>
    import { Inertia } from "@inertiajs/inertia";
    import { useForm } from "@inertiajs/inertia-vue3";
    import { AtAuthBox, AtAuthForm, AtInput, AtField } from "atmosphere-ui";


const form = useForm({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    terms: false,
});

const onHomePressed = () => {
    Inertia.visit('/');
}
const onLinkPressed = () => {
    Inertia.visit('login');
}

const submit = (formData) => {
    form
        .transform(data => ({
            ...data,
            email: formData.email,
            password: formData.password,
            password_confirmation: formData.confirmPassword,
            terms: true
    }))
    .post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation')
        }
    });
}
</script>
