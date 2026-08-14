<script setup lang="ts">
import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

import { router } from "@inertiajs/vue3";

// Primary path matches the copy ("activate this module in settings"): send the
// user straight to where modules are toggled instead of a dead-end home button.
const goToModules = () => {
    return router.visit('/user/preferences#modules')
}
const backToHome = () => {
    return router.visit('/')
}

</script>

<template>
    <AppLayout :title="$t('Unauthorized Module')">
      <main class="mt-4">
        <WelcomeCard message="Loger profiles">
            <section class="flex flex-col items-center pb-12 mx-auto">
                <img src="../LogerProfile/empty-box.svg" class="opacity-50" />
                <h4 class="text-lg font-bold text-body-1"> {{ $t("You don't have access to this module") }}</h4>
                <p class="max-w-lg my-3">
                   {{ $t('Activate this module in settings or contact the administrator') }}
                </p>

                <div class="flex flex-col items-center gap-3 mt-2">
                    <LogerButton variant="primary" @click="goToModules">
                        {{ $t('Activate module') }}
                    </LogerButton>
                    <button type="button" class="text-sm text-body-1/70 hover:text-body underline" @click="backToHome">
                        {{ $t('Back to Home') }}
                    </button>
                </div>
            </section>
        </WelcomeCard>
  </main>

    </AppLayout>
</template>
