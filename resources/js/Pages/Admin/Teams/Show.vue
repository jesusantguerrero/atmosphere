<script setup lang="ts">
import { ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";

interface TargetTeam {
    id: number;
    name: string;
    personal_team: boolean;
    created_at: string;
    updated_at: string;
    trial_ends_at: string | null;
    owner: { id: number; name: string; email: string } | null;
    users: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
        team_role: string | null;
    }>;
}

const props = defineProps<{ targetTeam: TargetTeam }>();

const showDeleteModal = ref(false);
const deleteForm = useForm({});

const confirmDelete = () => {
    deleteForm.delete(`/admin/teams/${props.targetTeam.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <AdminTemplate :title="targetTeam.name" :show-back-button="true">
        <!-- Header -->
        <section class="mb-6">
            <div class="flex items-center gap-2 mb-1">
                <h2 class="text-2xl font-bold text-body">{{ targetTeam.name }}</h2>
                <span
                    v-if="targetTeam.personal_team"
                    class="text-xs px-2 py-0.5 rounded bg-base-lvl-2 text-body-1/70"
                >
                    personal
                </span>
            </div>
            <p class="text-xs text-body-1/60">
                Created {{ new Date(targetTeam.created_at).toLocaleDateString() }} ·
                <span v-if="targetTeam.trial_ends_at">
                    Trial ends {{ new Date(targetTeam.trial_ends_at).toLocaleDateString() }}
                </span>
                <span v-else>No trial</span>
            </p>
        </section>

        <!-- Owner -->
        <section class="rounded-lg bg-base-lvl-3 border border-base p-5 mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-3">
                Owner
            </h3>
            <div v-if="targetTeam.owner" class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-body">{{ targetTeam.owner.name }}</div>
                    <div class="text-xs text-body-1/60">{{ targetTeam.owner.email }}</div>
                </div>
                <Link
                    :href="`/admin/users/${targetTeam.owner.id}`"
                    class="text-xs text-primary hover:underline"
                >
                    View user →
                </Link>
            </div>
            <p v-else class="text-sm text-body-1/60">No owner (orphaned team).</p>
        </section>

        <!-- Members -->
        <section class="rounded-lg bg-base-lvl-3 border border-base p-5 mb-4">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-3">
                Members ({{ targetTeam.users.length }})
            </h3>
            <ul v-if="targetTeam.users.length" class="divide-y divide-base">
                <li v-for="user in targetTeam.users" :key="user.id" class="py-2 flex items-center justify-between">
                    <Link :href="`/admin/users/${user.id}`" class="flex-1 min-w-0 hover:text-primary">
                        <div class="text-sm font-medium text-body truncate">{{ user.name }}</div>
                        <div class="text-xs text-body-1/60 truncate">{{ user.email }}</div>
                    </Link>
                    <span
                        v-if="user.team_role"
                        class="text-xs px-2 py-0.5 rounded-full bg-base-lvl-2 text-body-1"
                    >
                        {{ user.team_role }}
                    </span>
                </li>
            </ul>
            <p v-else class="text-sm text-body-1/60 text-center py-4">
                No members.
            </p>
        </section>

        <!-- Danger zone -->
        <section
            v-if="!targetTeam.personal_team"
            class="rounded-lg bg-base-lvl-3 border border-error/30 p-5"
        >
            <h3 class="text-sm font-semibold uppercase tracking-wide text-error mb-3">
                Danger zone
            </h3>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-body">Delete this team</p>
                    <p class="text-xs text-body-1/70 mt-1">
                        Removes the team and cascades through its data. Members are not
                        deleted. Cannot be undone. Super admin only.
                    </p>
                </div>
                <LogerButton variant="error" @click="showDeleteModal = true">
                    Delete team
                </LogerButton>
            </div>
        </section>

        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete this team?"
            @close="showDeleteModal = false"
        >
            <template #content>
                <p>You're about to delete <strong>{{ targetTeam.name }}</strong>.</p>
                <p class="mt-2 text-sm text-body-1/70">
                    Cascades through the team's data. Members keep their accounts.
                </p>
            </template>
            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <LogerButton variant="neutral" @click="showDeleteModal = false">
                        Cancel
                    </LogerButton>
                    <LogerButton variant="error" @click="confirmDelete" :processing="deleteForm.processing">
                        Delete team
                    </LogerButton>
                </div>
            </template>
        </ConfirmationModal>
    </AdminTemplate>
</template>
