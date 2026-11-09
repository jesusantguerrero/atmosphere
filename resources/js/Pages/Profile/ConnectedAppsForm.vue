<script lang="ts" setup>
    import { router } from '@inertiajs/vue3'
    import { ref } from 'vue'

    import ActionSection from '@/Components/atoms/ActionSection.vue'
    import { formatDate } from '@/utils'

    interface ConnectedApp {
        client_id: string,
        name: string,
        scopes: string[],
        connected_at: string | null,
        expires_at: string | null,
    }

    defineProps<{
        apps: ConnectedApp[],
    }>();

    // Apps the user approved on the OAuth consent screen (e.g. Claude Code through
    // MCP). Disconnecting revokes every token of that app at once.
    const scopeLabels: Record<string, string> = {
        'mcp:read': 'View your household finances',
        'mcp:write': 'Record transactions and mark payments as paid',
    };

    const disconnecting = ref<string | null>(null);
    const disconnect = (app: ConnectedApp) => {
        disconnecting.value = app.client_id;
        router.delete(route('user.connected-apps.destroy', app.client_id), {
            preserveScroll: true,
            onFinish: () => disconnecting.value = null,
        });
    };
</script>

<template>
    <ActionSection
        :title="$t('Connected apps')"
        :description="$t('Apps you allowed to use Loger on your behalf, like Claude Code.')">

        <template #content>
            <p v-if="!apps.length" class="text-sm text-body-1">
                {{ $t('No apps connected. Connect Claude Code with: claude mcp add --transport http loger {url}', { url: route('mcp.loger') }) }}
            </p>

            <ul v-else class="flex flex-col gap-3">
                <li
                    v-for="app in apps"
                    :key="app.client_id"
                    class="flex flex-col gap-3 rounded-lg border border-base bg-base-lvl-1 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-body">{{ app.name }}</p>
                        <p class="text-sm text-body-1">
                            {{ app.scopes.map(scope => $t(scopeLabels[scope] ?? scope)).join(' · ') }}
                        </p>
                        <p v-if="app.connected_at" class="text-xs text-body-1">
                            {{ $t('Connected on {date}', { date: formatDate(app.connected_at) }) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg border border-base px-3 py-2 text-sm font-semibold text-error hover:bg-base-lvl-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-50"
                        :disabled="disconnecting === app.client_id"
                        @click="disconnect(app)"
                    >
                        {{ $t('Disconnect') }}
                    </button>
                </li>
            </ul>
        </template>
    </ActionSection>
</template>
