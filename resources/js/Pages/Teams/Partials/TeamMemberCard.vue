<script lang="ts" setup>
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
