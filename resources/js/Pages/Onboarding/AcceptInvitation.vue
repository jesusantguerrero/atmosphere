<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
// @ts-expect-error: no definitions
import { AtButton } from "atmosphere-ui"

type Invitation = Record<string, string>

defineProps({
    invitations: {
        type: Array,
        default: () => ([])
    }
})

 function acceptTeamInvitation(invitation: Invitation) {
    useForm({}).patch(route('team-invitations.accept-internal', invitation), {
        preserveScroll: true,
    });
}

 function rejectTeamInvitation(invitation: Invitation) {
    useForm({}).delete(route('team-invitations.reject', invitation), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="w-full px-5 py-10 space-y-5 bg-white rounded-md">
        <p>To join an existing team, click the "accept invitation" button in the invitation mail.</p>

        <p>Didn't get the invitation mail? Ask a member of the organization to re-send the invitation.</p>

        <div v-for="invitation in invitations" :key="invitation.id" class="flex items-center justify-between px-4 py-2 rounded-md bg-base-lvl-1">
            <span>
                <strong>{{ invitation.team.owner.name }}</strong> has invited you to <strong class="text-primary">{{ invitation.team.name }}</strong>
            </span>

            <div class="flex space-x-2">
                <AtButton class="text-body-1 hover:text-error/80" @click="rejectTeamInvitation(invitation)"> Cancel </AtButton>
                <AtButton class="text-white rounded-md bg-primary" @click="acceptTeamInvitation(invitation)"> Accept </AtButton>
            </div>

        </div>
        <div class="w-full text-right">
            <button
                @click="$emit('change')"
                class="font-bold underline text-primary/80 hover:text-primary">
                Create my own Organization
            </button>
        </div>
    </div>
</template>
