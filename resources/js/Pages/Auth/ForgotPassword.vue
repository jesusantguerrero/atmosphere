<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            btn-label="Send email"
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
            <AtField label="Email">
                <AtInput v-model="form.email" required />
            </AtField>
        </template>
        </AtAuthForm>
    </AtAuthBox>
</template>

<script setup>
    import { AtAuthBox, AtAuthForm, AtField, AtInput } from "atmosphere-ui";
    import { useForm, Link } from "@inertiajs/vue3";

    defineProps({
        status: String
    });

    const form = useForm({
        email: ''
    })

    function submit() {
        form.post(route('password.email'))
    }
</script>
