<template>
    <AppLayout title="Settings - Integrations">
        <template #header>
            <SettingsSectionNav>
                <template #actions>
                    <LogerButton variant="inverse" class="text-white bg-primary"
                        @click="openAutomationModal"
                    >
                        Add Automation
                    </LogerButton>
                </template>
            </SettingsSectionNav>
        </template>
        <div class="py-12 pt-32 h-auto mx-auto space-y-4 max-w-7xl sm:px-6 lg:px-8">
            <div
                v-for="service in externalServices" :key="service.id"
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
                        {{ service?.automations.length }}
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
import { computed, nextTick, reactive } from "vue";
import { Inertia } from '@inertiajs/inertia';
import { AtButton } from "atmosphere-ui";
import AppLayout from "@/Layouts/AppLayout.vue";
import AutomationModal from '@/Components/AutomationModal.vue';
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

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

const externalServices = computed(() => {
    return props.services.filter(service => service.type == 'external');
})

const google = () => {
    const service = props.services.find(service => service.name = "Google");
    const credentials = {
        service_id: service.id,
        service_name: service.name,
    };
    axios({
        url: "/services/google",
        method: "post",
        data: {
            credentials
        }
    }).then(({ data }) => {
        location.href = data
    })
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
