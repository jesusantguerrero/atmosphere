<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            :btn-label="$t('Send reset link')"
            btn-class="mb-2 font-bold border-2 border-primary rounded-md bg-gradient-to-br from-purple-400 to-primary hover:bg-primary"
            link-class="text-primary hover:text-primary"
            v-model:isLoading="form.processing"
            mode="register"
            :errors="form.errors"
            @submit="submit"
            @home-pressed="onHomePressed"
            @link-pressed="onLinkPressed"
        >
         <template #brand>
            <Link href="/" class="w-full font-light font-brand">
                Loger DHM
            </Link>
          </template>
          <template #content>
            <div
                v-if="status"
                class="px-5 py-3 mt-6 mb-4 font-sans text-sm text-center rounded-md bg-black bg-opacity-25"
                role="status"
            >
                {{ status }}
            </div>
            <p v-else class="mt-6 mb-4 text-sm text-center">
                {{ $t('Enter your email and we will send you a link to choose a new password.') }}
            </p>
            <AtField :label="$t('Email')">
                <AtInput v-model="form.email" type="email" autocomplete="email" required />
            </AtField>
        </template>
        </AtAuthForm>
    </AtAuthBox>
</template>

<script setup>
    import { AtAuthBox, AtAuthForm, AtField, AtInput } from "atmosphere-ui";
    import { useForm, Link, router } from "@inertiajs/vue3";

    defineProps({
        status: String
    });

    const form = useForm({
        email: ''
    })

    function submit() {
        form.post(route('password.email'), {
            onSuccess: () => form.reset('email'),
        })
    }

    const onHomePressed = () => router.visit("/");
    const onLinkPressed = () => router.visit(route("login"));
</script>
