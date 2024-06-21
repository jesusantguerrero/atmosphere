<script setup lang="ts">
import { AtAuthBox, AtAuthForm } from "atmosphere-ui";
import { router } from "@inertiajs/vue3";
import { useForm, Link } from "@inertiajs/vue3";

import DemoInstructions from "./Partials/DemoInstructions.vue";
import AppIcon from "@/Components/AppIcon.vue";
import { config } from "@/config";

defineProps({
  canResetPassword: Boolean,
  status: String,
});

const form = useForm({
  email: "",
  password: "",
  remember: false,
});

const onHomePressed = () => {
  router.visit("/");
};

const onLinkPressed = () => {
  router.visit("register");
};

const submit = (formData: Record<string, string>) => {
  form
    .transform((data) => ({
      ...data,
      ...formData,
      remember: form.remember ? "on" : "",
    }))
    .post(route("login"), {
      onFinish: () => form.reset("password"),
    });
};

</script>

<template>
  <AtAuthBox>
    <AtAuthForm
      app-name="Loger"
      btn-class="mb-2 font-bold border-2 rounded-md border-primary bg-gradient-to-br from-purple-400 to-primary hover:bg-primary"
      link-class="text-primary hover:text-primary"
      v-model:isLoading="form.processing"
      :initial-values="form"
      :errors="form.errors"
      @submit="submit"
      @home-pressed="onHomePressed"
      @link-pressed="onLinkPressed"
    >
      <template #brand>
        <Link :to="{ name: 'landing' }" class="w-full h-20">
          <AppIcon size="huge" class="text-white" />
        </Link>
      </template>
      <template #prependInput v-if="config.IS_DEMO">
        <DemoInstructions
          class="px-5 py-2 mt-6 font-sans text-sm text-center bg-black bg-opacity-25 rounded-md"
        />
      </template>
    </AtAuthForm>
  </AtAuthBox>
</template>


