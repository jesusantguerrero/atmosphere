<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { AtInputPassword } from "atmosphere-ui";

import AppButton from "@/Components/atoms/LogerButton.vue";
import AppFormField from "@/Components/shared/AppFormField.vue";
import BaseSelect from "@/Components/shared/BaseSelect.vue";

const props = defineProps({
  configData: {
    type: Object,
    require: true,
    default: () => ({}),
  },
  isSaving: {
    type: Boolean,
    require: true,
    default: false,
  },
  isFetchingInitialData: {
    type: Boolean,
    require: true,
    default: false,
  },
  mailDrivers: {
    type: Array,
    require: true,
    default: () => [],
  },
});

const emit = defineEmits(["submit"]);

const encryptions = ["tls", "ssl", "starttls"].map((item) => ({
  id: item,
  label: item,
}));

const onSubmit = () => {
  emit(
    "submit",
    formData
      .transform((data) => ({
        encryption: data.encryption?.id,
        driver: data.driver?.id,
      }))
      .data()
  );
};

const formData = useForm({
  driver: {
    id: props.configData.driver,
    label: props.configData.driver,
  },
  host: props.configData.host,
  name: props.configData.name,
  username: props.configData.username,
  password: props.configData.password ?? "",
  port: props.configData.port,
  encryption: {
    id: props.configData.encryption,
    label: props.configData.encryption,
  },
  mail: props.configData.mail,
});
</script>

<template>
  <form @submit.prevent="onSubmit">
    <section class="grid grid-cols-2 gap-2">
      <AppFormField :label="$t('settings.mail.driver')" required>
        <BaseSelect v-model="formData.driver" :options="mailDrivers" />
      </AppFormField>

      <AppFormField
        :label="$t('settings.mail.host')"
        required
        v-model.trim="formData.host"
        type="text"
        name="mail_host"
      />

      <AppFormField
        :content-loading="isFetchingInitialData"
        :label="$t('settings.mail.username')"
        v-model.trim="formData.username"
        type="text"
        name="db_name"
      />

      <section class="flex">
        <AppFormField
          :label="$t('settings.mail.password')"
          :content-loading="isFetchingInitialData"
          name="password"
        >
          <AtInputPassword
            v-model.trim="formData.password"
            :content-loading="isFetchingInitialData"
            name="mail_port"
            class="bg-neutral/20 shadow-none rounded-md placeholder:first-letter:capitalize border-neutral hover:border-secondary/60 focus:border-secondary/60"
          />
        </AppFormField>
      </section>

      <AppFormField
        :label="$t('settings.mail.port')"
        :content-loading="isFetchingInitialData"
        required
        v-model.trim="formData.port"
        name="mail_port"
      />

      <AppFormField
        :label="$t('settings.mail.encryption')"
        :content-loading="isFetchingInitialData"
        required
      >
        <BaseSelect
          v-model.trim="formData.encryption"
          :options="encryptions"
          :searchable="true"
          :show-labels="false"
          placeholder="Select option"
        />
      </AppFormField>

      <AppFormField
        :label="$t('settings.mail.from_mail')"
        :content-loading="isFetchingInitialData"
        required
        v-model.trim="formData.mail"
        type="text"
        name="from_mail"
      />

      <AppFormField
        :label="$t('settings.mail.from_name')"
        :content-loading="isFetchingInitialData"
        required
        v-model.trim="formData.name"
        type="text"
        name="from_name"
      />
    </section>

    <div class="flex my-10">
      <AppButton
        :disabled="isSaving"
        :content-loading="isFetchingInitialData"
        :loading="isSaving"
        type="submit"
        variant="primary"
      >
        <template #left="slotProps">
          <BaseIcon v-if="!isSaving" name="SaveIcon" :class="slotProps.class" />
        </template>
        {{ $t("save") }}
      </AppButton>
      <slot />
    </div>
  </form>
</template>
