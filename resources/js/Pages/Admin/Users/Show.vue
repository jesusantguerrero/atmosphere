<script setup lang="ts">
import AppButton from "@/Components/shared/AppButton.vue";
import TeamMemberCard from "@/Pages/Teams/Partials/TeamMemberCard.vue";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import { router } from "@inertiajs/core";

defineProps<{
  teams: Record<string, string | Object>;
  user: Record<string, string>;
}>();

const impersonateUser = (user: Record<string, string>) => {
  router.post(`/admin/impersonate-user/${user.id}`);
};
</script>

<template>
  <AdminTemplate title="Teams">
    <main class="pb-16">
      {{ teams.name }}
      <TeamMemberCard :user="teams.owner">
        <template #actions>
          <AppButton @click="impersonateUser(teams.owner)"> Impersonate </AppButton>
        </template>
      </TeamMemberCard>
    </main>
  </AdminTemplate>
</template>
