<template>
    <div class="font-sans text-gray-900 antialiased">
        <div class="pt-4 bg-gray-100">
            <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
                <div>
                    <jet-authentication-card-logo />
                </div>

                <div v-html="terms" class="w-full sm:max-w-2xl mt-6 p-6 bg-base-lvl-1 shadow-md overflow-hidden sm:rounded-lg prose">
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import JetAuthenticationCardLogo from '@/Components/atoms/AuthenticationCardLogo.vue'

defineProps(['terms']);
</script>
