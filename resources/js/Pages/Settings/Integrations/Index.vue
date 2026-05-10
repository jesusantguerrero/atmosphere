<script setup lang="ts">
import { computed, nextTick, reactive, ref } from "vue";
import { router } from '@inertiajs/vue3';
import axios from 'axios';

import AppLayout from "@/Components/templates/AppLayout.vue";
import AutomationModal from '@/Components/AutomationModal.vue';
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

interface Service {
    id: number;
    name: string;
    label?: string;
    type: string;
    description?: string;
    logo?: string;
}

interface Automation {
    id: number;
    name?: string;
    label?: string;
}

interface Integration {
    id: number;
    name: string;
    hash: string;
    automation_service_id: number;
    created_at: string;
    last_synced_at: string | null;
    automations: Automation[];
}

const props = withDefaults(defineProps<{
    services?: Service[];
    integrations?: Integration[];
    recipes?: any[];
    tasks?: any[];
}>(), {
    services: () => [],
    integrations: () => [],
    recipes: () => [],
    tasks: () => [],
});

const state = reactive({
    isAutomationModalOpen: false,
    openedAutomation: {} as Record<string, unknown>,
    connectingServiceId: null as number | null,
    disconnectingId: null as number | null,
});

const expandedServiceId = ref<number | null>(null);

const externalServices = computed<Service[]>(() =>
    props.services.filter((service) => service.type === 'external')
);

const connectedServiceIds = computed<Set<number>>(() => {
    return new Set(props.integrations.map((i) => i.automation_service_id));
});

const availableServices = computed<Service[]>(() =>
    externalServices.value.filter((s) => !connectedServiceIds.value.has(s.id))
);

const totalAutomations = computed<number>(() =>
    props.integrations.reduce((sum, i) => sum + (i.automations?.length ?? 0), 0)
);

const formatDate = (iso: string): string => {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return iso;
    }
};

const formatRelativeTime = (iso: string | null): string => {
    if (!iso) return '';
    const then = new Date(iso).getTime();
    if (isNaN(then)) return '';

    const diff = Date.now() - then;
    if (diff < 0) return 'just now';

    const minute = 60_000;
    const hour = 3_600_000;
    const day = 86_400_000;

    if (diff < minute) return 'just now';
    if (diff < hour) return `${Math.floor(diff / minute)}m ago`;
    if (diff < day) return `${Math.floor(diff / hour)}h ago`;
    if (diff < 7 * day) return `${Math.floor(diff / day)}d ago`;
    return formatDate(iso);
};

const isGoogleLike = (service: { name: string }): boolean => {
    const name = (service.name || '').toLowerCase();
    return name === 'google' || name === 'gmail';
};

const integrationLabel = (integration: Integration): string => {
    return isGoogleLike(integration) ? 'Gmail' : integration.name;
};

const accessDetails = (service: Service): { items: string[]; note: string } => {
    if (isGoogleLike(service)) {
        return {
            items: [
                'Read-only access to email subjects and bodies — used to detect bank transactions.',
                'Read-only access to your Google profile (name and email) so we know which account you connected.',
                'No access to send, modify, or delete any email.',
            ],
            note: 'You can revoke access at any time from your Google Account settings, or from this page.',
        };
    }
    return {
        items: ['Read-only access. Loger never modifies data in connected services.'],
        note: 'You can disconnect at any time from this page.',
    };
};

const connectService = (service: Service): void => {
    if (!isGoogleLike(service)) return;
    state.connectingServiceId = service.id;

    axios
        .post('/services/google', {
            credentials: {
                service_id: service.id,
                service_name: service.name,
            },
        })
        .then(({ data }) => {
            window.location.href = data;
        })
        .catch(() => {
            state.connectingServiceId = null;
        });
};

const disconnect = async (integration: Integration): Promise<void> => {
    const confirmed = window.confirm(
        `Disconnect ${integration.name} (${integration.hash})? Loger will stop reading new messages from this account.`
    );
    if (!confirmed) return;

    state.disconnectingId = integration.id;
    try {
        await axios.delete(`/api/integrations/${integration.id}`);
        router.reload({ only: ['integrations'], preserveState: true });
    } finally {
        state.disconnectingId = null;
    }
};

const openAutomationModal = (): void => {
    state.openedAutomation = {};
    state.isAutomationModalOpen = true;
};

const onItemSaved = (): void => {
    nextTick(() => {
        state.isAutomationModalOpen = false;
        router.reload({ only: ['integrations'], preserveState: true });
    });
};
</script>

<template>
    <AppLayout title="Settings - Integrations">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="py-12 pt-32 mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-10">
            <!-- Page intro -->
            <header class="space-y-3">
                <h1 class="text-2xl font-bold text-body">{{ $t('Integrations') }}</h1>
                <p class="text-body-1/80 max-w-3xl">
                    {{ $t('Connect external services so Loger can sync automatically. Loger never sees your bank password — we only read transaction emails your bank already sends to your inbox.') }}
                </p>

                <!-- Trust strip -->
                <div class="flex flex-wrap gap-2 pt-2">
                    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-success/10 text-success border border-success/20">
                        <i class="fa fa-lock text-[10px]" />
                        {{ $t('Read-only access') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-base-lvl-2 text-body-1 border border-base">
                        <i class="fa fa-ban text-[10px]" />
                        {{ $t('Never sold') }}
                    </span>
                    <a
                        href="https://github.com/jesusantguerrero/atmosphere"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-base-lvl-2 text-body-1 border border-base hover:bg-base-lvl-1 transition"
                    >
                        <i class="fa fa-code text-[10px]" />
                        {{ $t('Open source — BSD-3') }}
                    </a>
                </div>
            </header>

            <!-- Sync sources (available, not yet connected) -->
            <section v-if="availableServices.length" class="space-y-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-body-1/60">
                        {{ $t('Sync sources') }}
                    </h2>
                    <p class="text-xs text-body-1/60 mt-0.5">
                        {{ $t('Connect a service to start automatic syncing.') }}
                    </p>
                </div>

                <article
                    v-for="service in availableServices"
                    :key="service.id"
                    class="bg-base-lvl-3 rounded-xl border border-base shadow-sm overflow-hidden"
                >
                    <div class="flex items-start gap-4 p-5">
                        <img
                            v-if="service.logo"
                            :src="service.logo"
                            :alt="service.name"
                            class="w-12 h-12 shrink-0 object-contain"
                        />
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-body">
                                {{ isGoogleLike(service) ? 'Gmail' : service.label ?? service.name }}
                            </h3>
                            <p class="text-sm text-body-1/80 mt-1">
                                <template v-if="isGoogleLike(service)">
                                    {{ $t('Loger reads transaction emails from your bank and turns them into entries automatically. No bank API, no Plaid — works with any bank that sends you email alerts.') }}
                                </template>
                                <template v-else>
                                    {{ service.description }}
                                </template>
                            </p>

                            <!-- What we access — expandable -->
                            <button
                                type="button"
                                class="text-xs text-primary hover:underline mt-2 inline-flex items-center gap-1"
                                @click="expandedServiceId = expandedServiceId === service.id ? null : service.id"
                            >
                                <i class="fa fa-chevron-right text-[9px] transition-transform" :class="{ 'rotate-90': expandedServiceId === service.id }" />
                                {{ $t('What we access') }}
                            </button>
                            <div v-if="expandedServiceId === service.id" class="mt-3 p-3 rounded-md bg-base-lvl-2 border border-base text-xs text-body-1/80 space-y-2">
                                <ul class="space-y-1.5 list-disc list-inside">
                                    <li v-for="item in accessDetails(service).items" :key="item">{{ item }}</li>
                                </ul>
                                <p class="text-body-1/60 pt-1 border-t border-base">{{ accessDetails(service).note }}</p>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <LogerButton
                                variant="primary"
                                :processing="state.connectingServiceId === service.id"
                                @click="connectService(service)"
                            >
                                <template #icon><i class="fa fa-link" /></template>
                                {{ $t('Connect') }}
                            </LogerButton>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Connected accounts -->
            <section v-if="integrations.length" class="space-y-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wide text-body-1/60">
                        {{ $t('Connected accounts') }}
                    </h2>
                    <p class="text-xs text-body-1/60 mt-0.5">
                        {{ $t('Loger is reading new messages from these accounts.') }}
                    </p>
                </div>

                <article
                    v-for="integration in integrations"
                    :key="integration.id"
                    class="bg-base-lvl-3 rounded-xl border border-base shadow-sm p-5"
                >
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="w-8 h-8 shrink-0 rounded-full bg-success/10 text-success flex items-center justify-center">
                                <i class="fa fa-check" />
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-bold text-body">
                                    {{ integrationLabel(integration) }}
                                </h3>
                                <p class="text-sm text-body-1/80 truncate">{{ integration.hash }}</p>
                                <p class="text-xs text-body-1/60 mt-1">
                                    <template v-if="integration.last_synced_at">
                                        <i class="fa fa-rotate text-success/70 text-[10px] mr-1" />
                                        {{ $t('Last synced') }} {{ formatRelativeTime(integration.last_synced_at) }}
                                    </template>
                                    <template v-else>
                                        <i class="fa fa-clock text-body-1/40 text-[10px] mr-1" />
                                        {{ $t('Awaiting first sync') }}
                                    </template>
                                    <span class="mx-1.5 text-body-1/30">·</span>
                                    {{ $t('Connected') }} {{ formatDate(integration.created_at) }}
                                    <span v-if="integration.automations?.length" class="ml-1.5">
                                        · {{ integration.automations.length }} {{ $t(integration.automations.length === 1 ? 'automation' : 'automations') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                type="button"
                                class="text-xs text-error hover:bg-error/10 px-3 py-2 rounded-md transition disabled:opacity-50"
                                :disabled="state.disconnectingId === integration.id"
                                @click="disconnect(integration)"
                            >
                                <i class="fa fa-unlink mr-1" />
                                {{ state.disconnectingId === integration.id ? $t('Disconnecting…') : $t('Disconnect') }}
                            </button>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Empty state — nothing connected and nothing available -->
            <section
                v-if="!availableServices.length && !integrations.length"
                class="bg-base-lvl-3 rounded-xl border border-base p-10 text-center"
            >
                <div class="text-4xl mb-3">🔌</div>
                <h2 class="font-bold text-body">{{ $t('No integrations available yet') }}</h2>
                <p class="text-sm text-body-1/70 mt-1 max-w-md mx-auto">
                    {{ $t('More sync sources are on the way. For now you can import bank statements as PDFs or enter transactions manually.') }}
                </p>
            </section>

            <!-- Automations — secondary section -->
            <section class="space-y-3 pt-4 border-t border-base">
                <div class="flex items-end justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-body-1/60">
                            {{ $t('Automations') }}
                            <span v-if="totalAutomations" class="ml-1 text-body-1/40 normal-case">({{ totalAutomations }})</span>
                        </h2>
                        <p class="text-xs text-body-1/60 mt-0.5 max-w-xl">
                            {{ $t('Optional rules that run when a connected service receives new data — for example, auto-categorizing a known payee.') }}
                        </p>
                    </div>
                    <LogerButton
                        v-if="integrations.length"
                        variant="inverse"
                        @click="openAutomationModal"
                    >
                        <template #icon><i class="fa fa-plus" /></template>
                        {{ $t('Add automation') }}
                    </LogerButton>
                </div>

                <div v-if="totalAutomations" class="space-y-2">
                    <template v-for="integration in integrations" :key="integration.id">
                        <div
                            v-for="automation in integration.automations"
                            :key="automation.id"
                            class="flex items-center justify-between px-4 py-3 rounded-lg bg-base-lvl-2 border border-base"
                        >
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-body truncate">
                                    {{ automation.label || automation.name || $t('Untitled automation') }}
                                </div>
                                <div class="text-xs text-body-1/60">{{ integrationLabel(integration) }} · {{ integration.hash }}</div>
                            </div>
                        </div>
                    </template>
                </div>
                <div v-else class="text-xs text-body-1/50 italic">
                    {{ integrations.length
                        ? $t('No automations yet. Add one to teach Loger how to handle new messages.')
                        : $t('Connect a sync source first to start adding automations.') }}
                </div>
            </section>

            <AutomationModal
                :show="state.isAutomationModalOpen"
                :record-data="state.openedAutomation"
                :closeable="true"
                title="Add a new automation"
                :services="services"
                :integrations="integrations"
                :recipes="recipes"
                :tasks="tasks"
                type="event"
                @close="state.isAutomationModalOpen = false"
                @saved="onItemSaved"
            />
        </div>
    </AppLayout>
</template>
