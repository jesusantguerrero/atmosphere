<script lang="ts" setup>
import { useForm, Link } from "@inertiajs/vue3";
import { ref, nextTick } from "vue";
import { AtAuthBox, AtAuthForm, AtField } from "atmosphere-ui";

import AppIcon from "@/Components/AppIcon.vue";
import JetAuthenticationCard from "@/Components/atoms/AuthenticationCard.vue";
import JetAuthenticationCardLogo from "@/Components/atoms/AuthenticationCardLogo.vue";
import JetButton from "@/Components/atoms/Button.vue";
import JetInput from "@/Components/atoms/Input.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import JetLabel from "@/Components/atoms/Label.vue";
import JetValidationErrors from "@/Components/atoms/ValidationErrors.vue";


    const recovery =  ref(false);
    const form = useForm({
        code: "",
        recovery_code: "",
    });

    const toggleRecovery = () => {
      recovery.value ^= true;

      nextTick(() => {
        if (recovery.value) {
          recoveryCode.value.focus();
          form.code = "";
        } else {
          code.value.focus();
          form.recovery_code = "";
        }
      });
    };

    const submit = () => {
      form.post(route("two-factor.login"));
    };
</script>

<template>
    <AtAuthBox>
        <AtAuthForm
            app-name="Loger"
            btn-class="mb-2 font-bold border-2 rounded-md border-primary bg-gradient-to-br from-purple-400 to-primary hover:bg-primary"
            link-class="text-primary hover:text-primary"
            v-model:isLoading="form.processing"
            :errors="form.errors"
            submit-label="Log in"
            :hide-link="false"
            @submit="submit"
            @home-pressed="onHomePressed"
            @link-pressed="onLinkPressed"
        >
        <template #brand>
            <Link :to="{ name: 'landing' }" class="w-full h-20">
              <AppIcon size="huge" class="text-white" />
            </Link>
          </template>

          <template #content>
              <div class="mb-4 px-5 py-2 mt-6 font-sans text-sm text-center bg-black bg-opacity-25 rounded-md">
                <template v-if="!recovery">
                  Please confirm access to your account by entering the authentication code provided
                  by your authenticator application.
                </template>

                <template v-else>
                  Please confirm access to your account by entering one of your emergency recovery
                  codes.
                </template>
              </div>

              <JetValidationErrors class="mb-4" />

              <article>
                <AtField v-if="!recovery" field="code" label="Code">
                  <LogerInput
                    ref="code"
                    id="code"
                    type="text"
                    inputmode="numeric"
                    class="block w-full mt-1 bg-base-lvl-3"
                    v-model="form.code"
                    autofocus
                    autocomplete="one-time-code"
                  />
                </AtField>

                <AtField v-else name="recovery_code" label="Recovery Code" >
                  <LogerInput
                    ref="recovery_code"
                    id="recovery_code"
                    type="text"
                    class="block w-full mt-1 bg-base-lvl-3"
                    v-model="form.recovery_code"
                    autocomplete="one-time-code"
                  />
                </AtField>

            </article>
        </template>
        <template #link>
            <div class="inline-flex items-center justify-end mt-4">
              <button
                type="button"
                class="text-sm text-gray-600 underline cursor-pointer hover:text-gray-900"
                @click.prevent="toggleRecovery"
              >
                <span v-if="!recovery"> Use a recovery code </span>

                <span v-else> Use an authentication code </span>
              </button>
            </div>
        </template>
        </AtAuthForm>
    </AtAuthBox>
  </template>
