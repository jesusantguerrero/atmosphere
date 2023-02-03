<template>
  <AtAuthBox>
    <AtAuthForm
      app-name="Loger"
      btn-class="mb-2 font-bold border-2 border-primary rounded-md bg-gradient-to-br from-purple-400 to-primary hover:bg-primary"
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
      <template #prependInput v-if="isDemo">
        <DemoInstructions
          class="rounded-md mt-6 text-center py-2 px-5 bg-black bg-opacity-25 text-sm font-sans"
        />
      </template>
    </AtAuthForm>
  </AtAuthBox>
</template>

<script setup>
import { AtAuthBox, AtAuthForm } from "atmosphere-ui";
import { router } from "@inertiajs/vue3";
import { useForm, Link } from "@inertiajs/vue3";
import DemoInstructions from "./Partials/DemoInstructions.vue";
import ApplicationLogo from "@/Components/atoms/ApplicationLogo.vue";
import AppIcon from "@/Components/AppIcon.vue";
import { isDemo } from "@/utils/constants";

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

const submit = (formData) => {
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
