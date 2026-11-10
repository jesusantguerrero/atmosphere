<script setup lang="ts">
import { Head } from "@inertiajs/vue3";

const props = defineProps<{
    client: { id: string; name: string };
    scopes: string[];
    authToken: string;
    state?: string | null;
    csrfToken: string;
    approveUrl: string;
    denyUrl: string;
}>();

// Passport answers approve/deny with a redirect to the app's localhost callback,
// so both are native form posts: an Inertia visit would follow it as XHR and fail.
const scopeLabels: Record<string, string> = {
    "mcp:read": "View your household finances",
    "mcp:write": "Record and edit transactions, schedule recurring payments, import statements and planner items, and mark payments as paid",
};
</script>

<template>
    <Head :title="$t('Authorize {app}', { app: props.client.name })" />
    <main class="flex min-h-screen items-center justify-center bg-base-lvl-1 px-4 py-10 text-body">
        <section class="w-full max-w-md rounded-xl border border-base bg-base-lvl-3 p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary-ink">Loger</p>
            <h1 class="mt-2 text-xl font-bold">
                {{ $t('{app} wants to access your home', { app: props.client.name }) }}
            </h1>
            <p class="mt-2 text-sm text-body-1">
                {{ $t('It will work with your current household. You can disconnect it any time from Settings → Security.') }}
            </p>

            <ul class="mt-5 flex flex-col gap-2">
                <li
                    v-for="scope in props.scopes"
                    :key="scope"
                    class="flex items-start gap-2 rounded-lg bg-base-lvl-1 px-3 py-2 text-sm"
                >
                    <span class="mt-0.5 text-success" aria-hidden="true">✓</span>
                    <span>{{ $t(scopeLabels[scope] ?? scope) }}</span>
                </li>
            </ul>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <form :action="props.denyUrl" method="POST">
                    <input type="hidden" name="_method" value="DELETE" />
                    <input type="hidden" name="_token" :value="props.csrfToken" />
                    <input type="hidden" name="state" :value="props.state ?? ''" />
                    <input type="hidden" name="client_id" :value="props.client.id" />
                    <input type="hidden" name="auth_token" :value="props.authToken" />
                    <button
                        type="submit"
                        class="w-full rounded-lg border border-base px-4 py-2 text-sm font-semibold text-body hover:bg-base-lvl-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary sm:w-auto"
                    >
                        {{ $t('Cancel') }}
                    </button>
                </form>
                <form :action="props.approveUrl" method="POST">
                    <input type="hidden" name="_token" :value="props.csrfToken" />
                    <input type="hidden" name="state" :value="props.state ?? ''" />
                    <input type="hidden" name="client_id" :value="props.client.id" />
                    <input type="hidden" name="auth_token" :value="props.authToken" />
                    <button
                        type="submit"
                        class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:opacity-90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:w-auto"
                    >
                        {{ $t('Authorize') }}
                    </button>
                </form>
            </div>
        </section>
    </main>
</template>
