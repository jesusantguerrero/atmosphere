<script setup>
import { reactive } from "vue";
import TabSelector from "@/Components/TabSelector.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

import { useTabs } from "@/utils/useTabs";
import AppLayout from "@/Components/templates/AppLayout.vue";
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";

const options = [
  {
    value: "whatsapp",
    label: "Whatsapp",
  },
  {
    value: "telegram",
    label: "Telegram",
  },
];

const config = reactive({
    whatsapp: {
        phoneNumberId: "",
        apiKey: "",
        template: ""
    },
    telegram: {
        botToken: "",
        chatId: "",
        message: ""
    },
    instagram: {
        accessToken: "",
        userId: ""
    },
    facebook: {
        accessToken: "",
        pageId: ""
    },
})

function integrate(platform) {
  console.log(`Integrating with ${platform}`);
  // Add API calls or form submission logic here
}

const { isTab, selectedTab } = useTabs(options, options[0].value)
</script>

<template>

    <AppLayout title="Settings - Integrations">
        <template #header>
            <SettingsSectionNav>
                <template #actions>
                    <LogerButton variant="inverse"
                        @click="openAutomationModal"
                    >
                        Save configuration
                    </LogerButton>
                </template>
            </SettingsSectionNav>
        </template>
        <div class="h-auto py-12 pt-32 mx-auto space-y-4 max-w-7xl sm:px-6 lg:px-8">

  <div class="p-6 max-w-4xl mx-auto bg-base-lvl-3">
    <h1 class="text-2xl font-bold mb-4">Messaging Integration</h1>

    <TabSelector :options="options" v-model="selectedTab" />

    <!-- WhatsApp Integration Form -->
    <section v-if="isTab('whatsapp')">
      <section class="mt-4">
        <section>
          <h2 class="text-xl font-semibold mb-4">WhatsApp Integration</h2>
          <div class="space-y-4">
            <div>
              <label for="wa-number">Phone Number</label>
              <LogerInput
                id="wa-number"
                v-model="config.whatsapp.phoneNumberId"
                placeholder="Enter WhatsApp Number"
              />
            </div>
            <div>
              <label for="wa-api-key">API Key</label>
              <LogerInput
                id="wa-api-key"
                v-model="config.whatsapp.apiKey"
                placeholder="Enter API Key"
              />
            </div>
            <div>
              <label for="wa-template">Message Template</label>
              <Textarea
                id="wa-template"
                v-model="config.whatsapp.template"
                placeholder="Type your message template here..."
              />
            </div>
            <Button class="w-full" @click="integrate('whatsapp')">
              <Send class="mr-2" size="16" /> Integrate WhatsApp
            </Button>
          </div>
        </section>
      </section>
    </section>

    <!-- Telegram Integration Form -->
    <section v-if="isTab('telegram')">
      <section class="mt-4">
        <section>
          <h2 class="text-xl font-semibold mb-4">Telegram Integration</h2>
          <div class="space-y-4">
            <div>
              <label for="tg-bot-token">Bot Token</label>
              <LogerInput
                id="tg-bot-token"
                v-model="config.telegram.botToken"
                placeholder="Enter Telegram Bot Token"
              />
            </div>
            <div>
              <label for="tg-chat-id">Chat ID</label>
              <LogerInput
                id="tg-chat-id"
                v-model="config.telegram.chatId"
                placeholder="Enter Chat ID"
              />
            </div>
            <div>
              <label for="tg-message">Default Message</label>
              <Textarea
                id="tg-message"
                v-model="config.telegram.message"
                placeholder="Type your default message here..."
              />
            </div>
            <Button class="w-full" @click="integrate('telegram')">
              <IMdiSend class="mr-2" size="16" /> Integrate Telegram
            </Button>
          </div>
        </section>
      </section>
    </section>
  </div>
</div>
</AppLayout>
</template>

<style scoped>
.tabs-list {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}
</style>
