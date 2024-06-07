<script lang="ts" setup>
import { ref } from "vue";
import { useForm, usePage, router } from "@inertiajs/vue3";
import ActionMessage from "@/Components/atoms/ActionMessage.vue";
import ActionSection from "@/Components/atoms/ActionSection.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";
import DangerButton from "@/Components/atoms/DangerButton.vue";
import DialogModal from "@/Components/atoms/DialogModal.vue";
import FormSection from "@/Components/atoms/FormSection.vue";
import InputError from "@/Components/atoms/InputError.vue";
import InputLabel from "@/Components/atoms/InputLabel.vue";
import PrimaryButton from "@/Components/atoms/PrimaryButton.vue";
import SecondaryButton from "@/Components/atoms/SecondaryButton.vue";
import SectionBorder from "@/Components/atoms/SectionBorder.vue";
import TextInput from "@/Components/atoms/TextInput.vue";

const props = defineProps({
  user: Object,
  availableRoles: Array,
  userPermissions: Object,
});

const displayableRole = (role) => {
  return props.availableRoles.find((r) => r.key === role).name;
};
</script>

<template>
  <div class="flex items-center justify-between">
    <div class="flex items-center">
      <img class="w-8 h-8 rounded-full" :src="user.profile_photo_url" :alt="user.name" />
      <div class="ml-4">
        {{ user.name }}
      </div>
    </div>

    <div class="flex items-center">
      <slot name="actions">
        <!-- Manage Team Member Role -->
        <button
          v-if="userPermissions?.canAddTeamMembers && availableRoles.length"
          class="ml-2 text-sm text-gray-400 underline"
          @click="manageRole(user)"
        >
          {{ displayableRole(user.membership.role) }}
        </button>

        <div v-else-if="availableRoles?.length" class="ml-2 text-sm text-gray-400">
          {{ displayableRole(user.membership.role) }}
        </div>

        <!-- Leave Team -->
        <button
          v-if="$page.props.user.id === user.id"
          class="cursor-pointer ml-6 text-sm text-red-500"
          @click="$emit('leaving')"
        >
          Leave
        </button>

        <!-- Remove Team Member -->
        <button
          v-else-if="userPermissions?.canRemoveTeamMembers"
          class="cursor-pointer ml-6 text-sm text-red-500"
          @click="$emit('remove')"
        >
          Remove
        </button>
      </slot>
    </div>
  </div>
</template>
