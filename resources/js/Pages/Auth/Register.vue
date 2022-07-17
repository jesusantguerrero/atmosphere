<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            mode="register"
            btn-class="mb-2 font-bold border-2 border-pink-400 rounded-md bg-gradient-to-br from-purple-400 to-pink-500 hover:bg-pink-500"
            link-class="text-pink-500 hover:text-pink-600"
            v-model:isLoading="form.processing"
            :errors="form.errors"
            @submit="submit"
            @home-pressed="onHomePressed"
            @link-pressed="onLinkPressed"
        >
            <template #brand>
                <Link :to="{name: 'landing'}" class="w-full font-light font-brand">
                    Loger DHM
                </Link>
            </template>
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
    import { useForm, Link } from "@inertiajs/inertia-vue3";
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
