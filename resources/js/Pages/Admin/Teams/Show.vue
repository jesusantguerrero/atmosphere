<script setup lang="ts">
import { router } from "@inertiajs/core";

import AppButton from "@/Components/shared/AppButton.vue";
import TeamMemberCard from "@/Pages/Teams/Partials/TeamMemberCard.vue";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import TeamBillingSection from "@/Components/templates/TeamBillingSection.vue";

defineProps<{
  teams: Record<string, string | Object>;
  user: Record<string, string>;
  sessions: any;
  plans: any;
  subscriptions: any;
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

      <TeamBillingSection
        :sessions="sessions"
        :plans="plans"
        :subscriptions="subscriptions"
        :team-id="teams.id"
      />
    </main>
  </AdminTemplate>
</template>
