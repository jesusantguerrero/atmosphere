<template>
    <JetAuthenticationCard>
        <template #logo>
            <JetAuthenticationCardLogo />
        </template>

        <div class="mb-4 text-sm text-gray-600">
            Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
        </div>

        <div class="mb-4 font-medium text-sm text-green-600" v-if="verificationLinkSent" >
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Resend Verification Email
                </JetButton>

                <Link :href="route('logout')" method="post" as="button" class="underline text-sm text-gray-600 hover:text-gray-900">Log Out</Link>
            </div>
        </form>
    </JetAuthenticationCard>
</template>

<script setup>
    import JetAuthenticationCard from '@/Components/atoms/AuthenticationCard.vue'
    import JetAuthenticationCardLogo from '@/Components/atoms/AuthenticationCardLogo.vue'
    import JetButton from '@/Components/atoms/Button.vue'
    import { Link, useForm } from "@inertiajs/vue3"
    import { computed } from 'vue';

    const props = defineProps({
            status: String
    });

    const form = useForm({})

    const submit = () => {
        form.post(route('verification.send'))
    }

    const verificationLinkSent = computed(() => {
        return props.status === 'verification-link-sent';
    })
</script>
