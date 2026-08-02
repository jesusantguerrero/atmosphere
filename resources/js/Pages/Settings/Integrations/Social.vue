<script setup lang="ts">
import { reactive } from "vue";
import { useForm } from "@inertiajs/vue3";

import TabSelector from "@/Components/TabSelector.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import AppLayout from "@/Components/templates/AppLayout.vue";
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";

import { useTabs } from "@/utils/useTabs";

/**
 * Social integrations settings — WhatsApp / Telegram.
 *
 * Previously the template referenced undefined components (`<Button>`,
 * `<Textarea>`, `<Send>`, `<IMdiSend>` with unresolved icons), an empty
 * `integrate()` stub, and an undefined `openAutomationModal` handler,
 * which meant the page rendered blank and the save button was dead.
 *
 * Rewritten to use LogerButton + native textarea, plus a real
 * useForm-backed submit that POSTs to `/settings/integrations/social`.
 * Instagram/Facebook were removed from the visible UI (they were in
 * state but had no matching template — half-finished draft).
 */

const options = [
    { value: "whatsapp", label: "Whatsapp" },
    { value: "telegram", label: "Telegram" },
];

const { isTab, selectedTab } = useTabs(options, options[0].value);

const config = reactive({
    whatsapp: {
        phoneNumberId: "",
        apiKey: "",
        template: "",
    },
    telegram: {
        botToken: "",
        chatId: "",
        message: "",
    },
});

const form = useForm({
    platform: "",
    payload: {} as Record<string, string>,
});

const integrate = (platform: "whatsapp" | "telegram") => {
    form
        .transform(() => ({
            platform,
            payload: config[platform],
        }))
        .post("/settings/integrations/social", {
            preserveScroll: true,
        });
};

const textareaClass =
    "w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary";
</script>

<template>
    <AppLayout title="Settings · Integrations">
        <template #header>
            <SettingsSectionNav />
        </template>

        <main class="px-3 mx-auto mt-12 mb-10 max-w-4xl sm:px-6 lg:px-8">
            <header class="mb-6">
                <h2 class="text-2xl font-bold text-body">
                    {{ $t('Messaging integrations') }}
                </h2>
                <p class="text-sm text-body-1 mt-1">
                    {{ $t('Connect WhatsApp or Telegram to send Loger notifications to your family channels.') }}
                </p>
            </header>

            <section class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <TabSelector :options="options" v-model="selectedTab" />

                <!-- WhatsApp -->
                <section v-if="isTab('whatsapp')" class="mt-6 space-y-4">
                    <div>
                        <label for="wa-number" class="block text-xs font-medium text-body-1 mb-1">
                            {{ $t('Phone Number ID') }}
                        </label>
                        <LogerInput
                            id="wa-number"
                            v-model="config.whatsapp.phoneNumberId"
                            placeholder="e.g. 1234567890"
                            class="text-sm"
                        />
                    </div>
                    <div>
                        <label for="wa-api-key" class="block text-xs font-medium text-body-1 mb-1">
                            {{ $t('API key') }}
                        </label>
                        <LogerInput
                            id="wa-api-key"
                            v-model="config.whatsapp.apiKey"
                            placeholder="Meta Business API access token"
                            class="text-sm"
                        />
                    </div>
                    <div>
                        <label for="wa-template" class="block text-xs font-medium text-body-1 mb-1">
                            {{ $t('Message template') }}
                        </label>
                        <textarea
                            id="wa-template"
                            v-model="config.whatsapp.template"
                            rows="3"
                            :class="textareaClass"
                            placeholder="Hi {{name}}, your budget for {{month}} is ready."
                        />
                    </div>
                    <LogerButton
                        variant="primary"
                        class="w-full"
                        @click="integrate('whatsapp')"
                        :processing="form.processing"
                    >
                        <IMdiSend class="mr-2" />
                        {{ $t('Save WhatsApp settings') }}
                    </LogerButton>
                </section>

                <!-- Telegram -->
                <section v-if="isTab('telegram')" class="mt-6 space-y-4">
                    <div>
                        <label for="tg-bot-token" class="block text-xs font-medium text-body-1 mb-1">
                            {{ $t('Bot token') }}
                        </label>
                        <LogerInput
                            id="tg-bot-token"
                            v-model="config.telegram.botToken"
                            placeholder="From @BotFather"
                            class="text-sm"
                        />
                    </div>
                    <div>
                        <label for="tg-chat-id" class="block text-xs font-medium text-body-1 mb-1">
                            {{ $t('Chat ID') }}
                        </label>
                        <LogerInput
                            id="tg-chat-id"
                            v-model="config.telegram.chatId"
                            placeholder="Your channel or group ID"
                            class="text-sm"
                        />
                    </div>
                    <div>
                        <label for="tg-message" class="block text-xs font-medium text-body-1 mb-1">
                            {{ $t('Default message') }}
                        </label>
                        <textarea
                            id="tg-message"
                            v-model="config.telegram.message"
                            rows="3"
                            :class="textareaClass"
                            placeholder="Weekly summary from Loger."
                        />
                    </div>
                    <LogerButton
                        variant="primary"
                        class="w-full"
                        @click="integrate('telegram')"
                        :processing="form.processing"
                    >
                        <IMdiSend class="mr-2" />
                        {{ $t('Save Telegram settings') }}
                    </LogerButton>
                </section>
            </section>
        </main>
    </AppLayout>
</template>
