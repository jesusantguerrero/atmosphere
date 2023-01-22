<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            btn-class="mb-2 font-bold border-2 border-primary rounded-md bg-gradient-to-br from-purple-400 to-primary hover:bg-primary"
            link-class="text-primary hover:text-primary"
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
    import { router } from "@inertiajs/vue3";
    import { useForm, Link } from "@inertiajs/vue3";

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
        router.visit('/');
    }

    const onLinkPressed = () => {
        router.visit('register');
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
