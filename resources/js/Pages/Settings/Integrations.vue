<template>
    <AppLayout title="Settings - Integrations">
        <template #header>
            <SettingsSectionNav>
                <template #actions>
                    <AtButton class="text-white bg-primary"
                        @click="openAutomationModal"
                    >
                        Add Automation
                    </AtButton>
                </template>
            </SettingsSectionNav>
        </template>
        <div class="py-12 pt-32 h-auto mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div
                v-for="service in services" :key="service.id"
                @click="handleCommand(service)"
                class="flex px-5 py-3 bg-base-lvl-3 rounded-md shadow-xl cursor-pointer">
            <div>
                <img :src="service.logo" class="w-9" />
            </div>
            <h2 class="ml-5">
                {{ service.name }}
            </h2>
            <p class="ml-2">
                {{ service.description }}
            </p>
            </div>

            <div class="w-full integrations-form">
                <div
                    class="grid grid-cols-3 px-5 py-3 my-2 font-bold text-gray-500 bg-base-lvl-1 cursor-pointer app-service__item"
                    v-for="service in integrations"
                    :key="service.id"
                >
                    <div class="left">
                        <div class="head">
                            {{ service.name }} {{ service.hash }}
                        </div>
                        <div class="tagline">
                            {{ service.hash }} {{ service.created_at }}
                        </div>
                    </div>

                    <div class="text-right automations">
                        {{ service.automations.length }}
                    </div>

                    <div class="text-right options">options</div>
                </div>
            </div>

            <AutomationModal
                :show="state.isAutomationModalOpen"
                :record-data="openedAutomation"
                :closeable="true"
                title="Add a new automation"
                :services="services"
                :integrations="integrations"
                :recipes="recipes"
                :tasks="tasks"
                type="event"
                @cancel="state.isAutomationModalOpen = false"
                @saved="onItemSaved"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { nextTick, reactive } from "vue";
import { Inertia } from '@inertiajs/inertia';
import { AtButton } from "atmosphere-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import AutomationModal from '@/Components/AutomationModal.vue';
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";

const props = defineProps({
    services: {
        type: Array,
        default() {
            return [];
        }
    },
    integrations: {
        type: Array,
        default() {
            return [];
        }
    },
    recipes: {
        type: Array,
        default() {
            return [];
        }
    },
    tasks: {
        type: Array,
        default() {
            return [];
        }
    },
})

const state = reactive({
    showAddConnection: false,
    authInstance: null,
    isAutomationModalOpen: false,
    openedAutomation: {}
});

const toggleAppConnection = () => {
    state.showAddConnection = !state.showAddConnection;
}

const openAutomationModal = (board, stage) => {
    state.isAutomationModalOpen = true;
    state.openedAutomation = {
        board,
        stage
    };
}

const onItemSaved = () => {
    nextTick(() => {
        state.isAutomationModalOpen = false;
        Inertia.reload(`/integrations`, {
            only: ["integrations"],
            preserveState: true
        });
    });
}

const handleCommand = (service) => {
    switch (service.name.toLowerCase()) {
        case "google":
        case "gmail":
            google(service.name.toLowerCase(), service);
            break;
        default:
            break;
    }
}

const google = (scopeName, service) => {
    gapi.load("auth2", () => {
        gapi.auth2
            .init({
                apiKey: process.env.MIX_GOOGLE_APP_KEY,
                clientId: process.env.MIX_GOOGLE_CLIENT_ID,
                accessType: "offline",
                scope: `profile https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/calendar.readonly`,
                discoveryDocs: [
                    "https://www.googleapis.com/discovery/v1/apis/calendar/v3/rest",
                    "https://sheets.googleapis.com/discovery/rest?version=v4"
                ]
            })
            .then(async () => {
                const authInstance = gapi.auth2.getAuthInstance();
                const user = authInstance.currentUser.get();
                if (!user.getAuthResponse().session_state) {
                    await authInstance.signIn();
                }
                const profile = user.getBasicProfile();
                await authInstance
                    .grantOfflineAccess({
                        authuser: user.getAuthResponse().session_state.extraQueryParams.authuser
                    })
                    .then(({ code }) => {
                        const credentials = {
                            code,
                            service_id: service.id,
                            service_name: service.name,
                            user: profile.getEmail()
                        };
                        axios.post('/services/google', { credentials }).then(() => {
                            Inertia.get(`/integrations`, {
                                only: ["integrations"],
                                preserveState: true
                            });
                        });
                    })
            })
    });
}
</script>

<style lang="scss">
    .app-service__integration {
        @apply bg-base-lvl-1 text-gray-500 my-2 cursor-pointer px-5 py-3 font-bold;
        @apply border-2 border-gray-300 rounded-md;
        width: 150px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
</style>
