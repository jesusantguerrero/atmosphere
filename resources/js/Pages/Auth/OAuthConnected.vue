<script setup lang="ts">
import { Head } from "@inertiajs/vue3";
import { onMounted } from "vue";

const props = defineProps<{
    client: { name: string };
    redirectUrl: string;
}>();

// The app is waiting on its localhost callback for the code: hand it over
// after a beat so the confirmation is readable, or right away on click.
const REDIRECT_DELAY_MS = 1500;

const continueToApp = () => {
    window.location.replace(props.redirectUrl);
};

onMounted(() => {
    window.setTimeout(continueToApp, REDIRECT_DELAY_MS);
});
</script>

<template>
    <Head :title="$t('{app} connected', { app: props.client.name })" />
    <main class="flex min-h-screen items-center justify-center bg-base-lvl-1 px-4 py-10 text-body">
        <section class="w-full max-w-md rounded-xl border border-base bg-base-lvl-3 p-6 text-center shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-ink">Loger</p>
            <div
                class="mx-auto mt-4 flex h-12 w-12 items-center justify-center rounded-full bg-success/15 text-2xl text-success"
                aria-hidden="true"
            >
                ✓
            </div>
            <h1 class="mt-4 text-xl font-bold">
                {{ $t('{app} is connected to Loger', { app: props.client.name }) }}
            </h1>
            <p class="mt-2 text-sm text-body-1">
                {{ $t('Returning to {app}…', { app: props.client.name }) }}
            </p>
            <button
                type="button"
                class="mt-6 w-full rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                @click="continueToApp"
            >
                {{ $t('Continue') }}
            </button>
        </section>
    </main>
</template>
