<script setup lang="ts">
import { ref, computed, reactive } from "vue";
import { useForm } from "@inertiajs/vue3";

import Smtp from "@/Pages/Settings/Partials/MailDriverSmtp.vue";
// import MailTestModal from "@/scripts/admin/components/modal-components/MailTestModal.vue";
import BaseSettingSection from "./BaseSettingSection.vue";
import AppButton from "@/Components/atoms/LogerButton.vue";

const props = defineProps<{
  settingData: Record<string, string>;
}>();

let isFetchingInitialData = ref(false);

const mailConfigData = reactive({
  ...props.settingData,
});

const mailDriver = computed(() => {
  if (formData.mail_driver == "smtp") return Smtp;
  return Smtp;
});

const mailDrivers = ["smtp", "mail", "sendmail", "mailgun", "ses"].map((item) => ({
  id: item,
  label: item,
}));

const formData = useForm({
  mailDriver: "smtp",
});
async function saveEmailConfig(data) {
  formData
    .transform(() => ({
      ...data,
      encryption: data.encryption?.id,
      driver: data.driver?.id,
    }))
    .post("/admin/settings/mail", {
      preserveScroll: true,
      onSuccess() {},
      onError() {
        console.error(e);
      },
    });
}
</script>

<template>
  <BaseSettingSection>
    <section class="">
      <component
        :is="mailDriver"
        :config-data="mailConfigData"
        :is-saving="formData.processing"
        :mail-drivers="mailDrivers"
        @submit="saveEmailConfig"
      >
        <AppButton
          variant="primary-outline"
          type="button"
          class="ml-2"
          :processing="isFetchingInitialData"
          @click="openMailTestModal"
        >
          {{ $t("general.test_email_conf") }}
        </AppButton>
      </component>
    </section>
  </BaseSettingSection>
</template>
