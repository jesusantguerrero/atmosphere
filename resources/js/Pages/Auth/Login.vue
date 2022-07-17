<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
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
        </AtAuthForm>
    </AtAuthBox>
</template>

<script setup>
    import { AtAuthBox, AtAuthForm } from "atmosphere-ui";
    import { Inertia } from "@inertiajs/inertia";
    import { useForm, Link } from "@inertiajs/inertia-vue3";

    defineProps({
        canResetPassword: Boolean,
        status: String,
    });

    const form = useForm({
        email: '',
        password: '',
        remember: false
    })

    const onHomePressed = () => {
        Inertia.visit('/');
    }

    const onLinkPressed = () => {
        Inertia.visit('register');
    }

    const submit = (formData) => {
        form
            .transform(data => ({
                ...data,
                ... formData,
                remember: form.remember ? 'on' : ''
            }))
            .post(route('login'), {
                onFinish: () => form.reset('password'),
            })
    }
</script>
