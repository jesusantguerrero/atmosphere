<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            btn-label="Send email"
            btn-class="mb-2 font-bold border-2 border-primary-400 rounded-md bg-gradient-to-br from-purple-400 to-primary-500 hover:bg-primary-500"
            link-class="text-primary-500 hover:text-primary-600"
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
    import { useForm, Link } from "@inertiajs/inertia-vue3";

    defineProps({
        status: String
    });

    const form = useForm({
        email: ''
    })

    function submit() {
        this.form.post(this.route('password.email'))
    }
</script>
