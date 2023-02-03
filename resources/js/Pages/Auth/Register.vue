<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            mode="register"
            btn-class="mb-2 font-bold border-2 border-primary rounded-md bg-gradient-to-br from-purple-400 to-primary hover:bg-primary"
            link-class="text-primary hover:text-primary"
            v-model:isLoading="form.processing"
            :initial-values="form"
            :config="formConfig"
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
import { useForm, Link, router } from "@inertiajs/vue3";
import { AtAuthBox, AtAuthForm, AtInput, AtField } from "atmosphere-ui";
import { computed, onMounted, ref } from "vue";


const form = useForm({
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    terms: false,
});

const onHomePressed = () => {
    router.visit('/');
}
const onLinkPressed = () => {
    router.visit('login');
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

const fixedEmail = ref(null)
onMounted(() => {
    const thisUrl = new URL(window.location.href)
    fixedEmail.value = thisUrl.searchParams.get('email');
    if (fixedEmail.value) {
        form.email = fixedEmail.value
    }
})

const formConfig = computed(() => {
    return {
        email: {
            disabled: fixedEmail.value
        }
    }
})
</script>
