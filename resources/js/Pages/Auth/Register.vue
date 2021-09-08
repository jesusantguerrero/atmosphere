<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            mode="register"
            btn-class="text-white bg-pink-500 hover:bg-pink-600"
            link-class="text-pink-500 hover:text-pink-600"
            v-model:isLoading="form.processing"
            :errors="form.errors"
            @submit="submit"
            @home-pressed="onHomePressed"
            @link-pressed="onLinkPressed"
        >
            <template #prependInput>
                <at-field label="Name">
                    <at-input v-model="form.name" required/>
                </at-field>
            </template>
        </AtAuthForm>
  </AtAuthBox>
</template>

<script>
    import { AtAuthBox, AtAuthForm, AtInput, AtField } from "atmosphere-ui";

    export default {
        components: {
            AtAuthBox,
            AtAuthForm,
            AtInput,
            AtField
        },

        data() {
            return {
                form: this.$inertia.form({
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                    terms: false,
                })
            }
        },

        methods: {
            onHomePressed() {
                this.$inertia.visit('/');
            },
            onLinkPressed() {
                this.$inertia.visit('login');
            },

            submit(formData) {
                this.form
                 .transform(data => ({
                        ...data,
                        email: formData.email,
                        password: formData.password,
                        password_confirmation: formData.confirmPassword,
                        terms: true
                }))
                .post(this.route('register'), {
                    onFinish: () => {
                        this.form.reset('password', 'password_confirmation')
                        this.$inertia.visit('login')
                    }
                })
            }
        }
    }
</script>
