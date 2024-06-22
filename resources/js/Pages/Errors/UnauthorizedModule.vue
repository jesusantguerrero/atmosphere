<script setup lang="ts">
import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

import { router } from "@inertiajs/vue3";

const backToHome = () => {
    return router.visit('/')
}

</script>

<template>
    <AppLayout title="Unauthorized Module">
      <main class="mt-4">
        <WelcomeCard message="Loger profiles">
            <section class="flex flex-col items-center pb-12 mx-auto">
                <img src="../LogerProfile/empty-box.svg" class="opacity-50" />
                <h4 class="text-lg font-bold text-body-1"> You don't have access to this module</h4>
                <p class="max-w-lg my-3">
                   Activate this module in settings or contact the administrator
                </p>

                <LogerButton variant="inverse" @click="backToHome">
                    Back to Home
                </LogerButton>
            </section>
        </WelcomeCard>
  </main>

    </AppLayout>
</template>
