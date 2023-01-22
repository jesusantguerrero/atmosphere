<template>
    <div class="bg-white w-full space-y-5 px-5 py-10 rounded-md">
        <p>To join an existing team, click the "accept invitation" button in the invitation mail.</p>

        <p>Didn't get the invitation mail? Ask a member of the organization to re-send the invitation.</p>

        <div v-for="invitation in invitations" :key="invitation.id" class="flex  items-center justify-between rounded-md bg-base-lvl-1 px-4 py-2">
            <span>
                <strong>{{ invitation.team.owner.name }}</strong> has invited you to <strong class="text-primary">{{ invitation.team.name }}</strong>
            </span>

            <div class="flex space-x-2">
                <AtButton class="text-body-1 hover:text-error/80" @click="rejectTeamInvitation(invitation)"> Cancel </AtButton>
                <AtButton class="rounded-md bg-primary text-white" @click="acceptTeamInvitation(invitation)"> Accept </AtButton>
            </div>

        </div>
        <div class="w-full text-right">
            <button
                @click="$emit('change')"
                class="text-primary/80 hover:text-primary font-bold underline">
                Create my own Organization
            </button>
        </div>
    </div>
</template>

<script setup>

import { useForm } from "@inertiajs/vue3";
import { AtButton } from "atmosphere-ui"

defineProps({
    invitations: {
        type: Array,
        default: () => ([])
    }
})

 function acceptTeamInvitation(invitation) {
    useForm({}).patch(route('team-invitations.accept-internal', invitation), {
        preserveScroll: true,
    });
}

 function rejectTeamInvitation(invitation) {
    useForm({}).delete(route('team-invitations.reject', invitation), {
        preserveScroll: true,
    });
}
</script>
