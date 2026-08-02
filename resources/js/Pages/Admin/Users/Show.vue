<script setup lang="ts">
import { ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";

interface TargetUser {
    id: number;
    name: string;
    email: string;
    role: "user" | "admin" | "super_admin";
    created_at: string;
    updated_at: string;
    email_verified_at: string | null;
    two_factor_enabled: boolean;
    profile_photo_url: string;
    is_super_admin: boolean;
    can_be_impersonated: boolean;
    owned_teams: Array<{ id: number; name: string; created_at: string }>;
    teams: Array<{ id: number; name: string; user_id: number; created_at: string }>;
}

const props = defineProps<{ targetUser: TargetUser }>();

const roleForm = useForm({ role: props.targetUser.role });
const updateRole = () => {
    roleForm.patch(`/admin/users/${props.targetUser.id}/role`, {
        preserveScroll: true,
    });
};

const impersonate = () => {
    router.post(
        `/admin/impersonate/${props.targetUser.id}`,
        {},
        { preserveScroll: false }
    );
};

const showDeleteModal = ref(false);
const deleteForm = useForm({});
const confirmDelete = () => {
    deleteForm.delete(`/admin/users/${props.targetUser.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <AdminTemplate :title="targetUser.name" :show-back-button="true">
        <!-- Header card -->
        <section class="flex flex-col md:flex-row gap-4 items-start mb-6">
            <img
                :src="targetUser.profile_photo_url"
                :alt="targetUser.name"
                class="w-20 h-20 rounded-full border-2 border-base"
            />
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold text-body">{{ targetUser.name }}</h2>
                <p class="text-body-1">{{ targetUser.email }}</p>
                <p class="text-xs text-body-1/60 mt-1">
                    Joined {{ new Date(targetUser.created_at).toLocaleDateString() }} ·
                    <span v-if="targetUser.email_verified_at" class="text-success">Verified</span>
                    <span v-else class="text-warning">Unverified</span>
                    <span v-if="targetUser.two_factor_enabled" class="ml-2 text-success">2FA</span>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <LogerButton
                    v-if="targetUser.can_be_impersonated"
                    variant="inverse"
                    @click="impersonate"
                >
                    Impersonate
                </LogerButton>
                <LogerButton
                    v-else
                    variant="inverse"
                    disabled
                    title="Cannot impersonate a super admin"
                >
                    Impersonate
                </LogerButton>
            </div>
        </section>

        <!-- Two-column layout: role/actions on left, teams on right -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Role management -->
            <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-4">
                    Role
                </h3>
                <select
                    v-model="roleForm.role"
                    class="w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                    :disabled="targetUser.is_super_admin"
                >
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super admin</option>
                </select>
                <p v-if="targetUser.is_super_admin" class="text-xs text-body-1/60 mt-2">
                    This user is the env-configured owner. Role changes are locked.
                </p>
                <LogerButton
                    variant="primary"
                    class="mt-3 w-full"
                    @click="updateRole"
                    :processing="roleForm.processing"
                    :disabled="roleForm.role === targetUser.role"
                >
                    Update role
                </LogerButton>
            </div>

            <!-- Danger zone -->
            <div class="rounded-lg bg-base-lvl-3 border border-error/30 p-5 lg:col-span-2">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-error mb-4">
                    Danger zone
                </h3>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-body">Delete this user</p>
                        <p class="text-xs text-body-1/70 mt-1">
                            Cascades through all their team memberships. Cannot be undone.
                            Super admin only.
                        </p>
                    </div>
                    <LogerButton
                        variant="error"
                        @click="showDeleteModal = true"
                        :disabled="targetUser.is_super_admin"
                    >
                        Delete
                    </LogerButton>
                </div>
            </div>
        </section>

        <!-- Teams -->
        <section class="mt-4 rounded-lg bg-base-lvl-3 border border-base p-5">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-4">
                Teams ({{ targetUser.teams.length }})
            </h3>
            <ul v-if="targetUser.teams.length" class="divide-y divide-base">
                <li v-for="team in targetUser.teams" :key="team.id" class="py-2 flex items-center justify-between">
                    <Link :href="`/admin/teams/${team.id}`" class="text-sm text-body hover:text-primary">
                        {{ team.name }}
                    </Link>
                    <span v-if="team.user_id === targetUser.id" class="text-xs text-primary">Owner</span>
                </li>
            </ul>
            <p v-else class="text-sm text-body-1/60 text-center py-4">
                No teams.
            </p>
        </section>

        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete this user?"
            @close="showDeleteModal = false"
        >
            <template #content>
                <p>You're about to delete <strong>{{ targetUser.name }}</strong> ({{ targetUser.email }}).</p>
                <p class="mt-2 text-sm text-body-1/70">
                    This will remove their team memberships and cannot be undone.
                </p>
            </template>
            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <LogerButton variant="neutral" @click="showDeleteModal = false">
                        Cancel
                    </LogerButton>
                    <LogerButton variant="error" @click="confirmDelete" :processing="deleteForm.processing">
                        Delete user
                    </LogerButton>
                </div>
            </template>
        </ConfirmationModal>
    </AdminTemplate>
</template>
