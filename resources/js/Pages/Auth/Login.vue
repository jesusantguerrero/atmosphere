<template>
    <at-auth-box>
        <at-auth-form
            app-name="Loger"
            btn-class="text-white bg-pink-500 hover:bg-pink-600"
            link-class="text-pink-500 hover:text-pink-600"
            v-model:isLoading="form.processing"
            :errors="errors"
            @submit="submit"
            @home-pressed="onHomePressed"
            @link-pressed="onLinkPressed"
        />
    </at-auth-box>
</template>

<script>
    import { AtAuthBox, AtAuthForm, AtButton } from "atmosphere-ui/dist/atmosphere-ui.es.js";

    export default {
        components: {
            AtAuthBox,
            AtAuthForm,
            AtButton
        },

        props: {
            canResetPassword: Boolean,
            status: String
        },

        data() {
            return {
                form: this.$inertia.form({
                    email: '',
                    password: '',
                    remember: false
                })
            }
        },

        methods: {
              onHomePressed() {
                this.$inertia.visit('/');
            },

            onLinkPressed() {
                this.$inertia.visit('register');
            },

            submit(formData) {
                this.form
                    .transform(data => ({
                        ...data,
                        ... formData,
                        remember: this.form.remember ? 'on' : ''
                    }))
                    .post(this.route('login'), {
                        onFinish: () => this.form.reset('password'),
                    })
            }
        }
    }
</script>
