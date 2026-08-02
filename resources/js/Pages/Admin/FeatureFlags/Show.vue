<script setup lang="ts">
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import ConfirmationModal from "@/Components/atoms/ConfirmationModal.vue";

interface Flag {
    id: number;
    key: string;
    name: string;
    description: string | null;
    scope: "global" | "team" | "user";
    category: string;
    enabled_by_default: boolean;
    rollout_percentage: number;
    metadata: Record<string, unknown>;
    created_at: string;
    updated_at: string;
}

interface Override {
    id: number;
    scope_type: "Team" | "User";
    scope_id: number;
    scope_label: string;
    scope_email: string | null;
    enabled: boolean;
    reason: string | null;
    created_at: string;
}

const props = defineProps<{ flag: Flag; overrides: Override[] }>();

// -------- Global settings form (name, description, category, rollout, default) --------
const settingsForm = useForm({
    name: props.flag.name,
    description: props.flag.description ?? "",
    category: props.flag.category,
    enabled_by_default: props.flag.enabled_by_default,
    rollout_percentage: props.flag.rollout_percentage,
});

const saveSettings = () => {
    settingsForm.patch(`/admin/feature-flags/${props.flag.id}`, {
        preserveScroll: true,
    });
};

// -------- Override form --------
const overrideForm = useForm({
    scope_type: "User" as "User" | "Team",
    scope_id: null as number | null,
    enabled: true,
    reason: "",
});

const submitOverride = () => {
    overrideForm.post(`/admin/feature-flags/${props.flag.id}/overrides`, {
        preserveScroll: true,
        onSuccess: () => overrideForm.reset(),
    });
};

const removeOverride = (id: number) => {
    router.delete(
        `/admin/feature-flags/${props.flag.id}/overrides/${id}`,
        { preserveScroll: true }
    );
};

// -------- Delete flag --------
const showDeleteModal = ref(false);
const deleteForm = useForm({});
const confirmDelete = () => {
    deleteForm.delete(`/admin/feature-flags/${props.flag.id}`, {
        onSuccess: () => (showDeleteModal.value = false),
    });
};
</script>

<template>
    <AdminTemplate :title="flag.name" :show-back-button="true">
        <!-- Header -->
        <section class="mb-6">
            <h2 class="text-2xl font-bold text-body">{{ flag.name }}</h2>
            <p class="text-xs text-body-1/60 font-mono mt-1">{{ flag.key }}</p>
            <p v-if="flag.description" class="text-sm text-body-1 mt-2 max-w-2xl">
                {{ flag.description }}
            </p>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Settings (left, 2/3) -->
            <section class="lg:col-span-2 space-y-4">
                <!-- Global toggle + rollout -->
                <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-4">
                        Global default
                    </h3>

                    <label class="flex items-center gap-3 mb-4 cursor-pointer">
                        <input
                            v-model="settingsForm.enabled_by_default"
                            type="checkbox"
                            class="rounded"
                        />
                        <div>
                            <div class="text-sm font-medium text-body">Enabled by default</div>
                            <div class="text-xs text-body-1/60">
                                When ON, every user gets this feature unless an override disables it.
                            </div>
                        </div>
                    </label>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-body-1 mb-2">
                            Rollout percentage: <span class="text-body font-semibold">{{ settingsForm.rollout_percentage }}%</span>
                        </label>
                        <input
                            v-model.number="settingsForm.rollout_percentage"
                            type="range"
                            min="0"
                            max="100"
                            step="1"
                            class="w-full"
                        />
                        <p class="text-xs text-body-1/60 mt-1">
                            Deterministic hash-bucket. 0 = nobody (default only), 100 = everybody.
                            Applied on top of the global default.
                        </p>
                    </div>
                </div>

                <!-- Metadata (name/description/category) -->
                <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-4">
                        Metadata
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Display name</label>
                            <LogerInput v-model="settingsForm.name" class="text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Description</label>
                            <textarea
                                v-model="settingsForm.description"
                                rows="2"
                                class="w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Category</label>
                            <select
                                v-model="settingsForm.category"
                                class="px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                            >
                                <option value="experimental">Experimental</option>
                                <option value="gating">Gating</option>
                                <option value="kill_switch">Kill switch</option>
                                <option value="operations">Operations</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <LogerButton
                        variant="primary"
                        @click="saveSettings"
                        :processing="settingsForm.processing"
                        :disabled="!settingsForm.isDirty"
                    >
                        Save settings
                    </LogerButton>
                </div>

                <!-- Danger zone -->
                <div class="rounded-lg bg-base-lvl-3 border border-error/30 p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-error mb-3">
                        Danger zone
                    </h3>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-body">Delete this flag</p>
                            <p class="text-xs text-body-1/70 mt-1">
                                Any code still checking <code class="text-primary">{{ flag.key }}</code> will
                                get <code>false</code> forever. Soft-deleted, so restorable via DB.
                            </p>
                        </div>
                        <LogerButton variant="error" @click="showDeleteModal = true">
                            Delete flag
                        </LogerButton>
                    </div>
                </div>
            </section>

            <!-- Overrides (right, 1/3) -->
            <aside class="lg:col-span-1 space-y-4">
                <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-4">
                        Add override
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Scope</label>
                            <select
                                v-model="overrideForm.scope_type"
                                class="w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                            >
                                <option value="User">User</option>
                                <option value="Team">Team</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">
                                {{ overrideForm.scope_type }} ID
                            </label>
                            <LogerInput v-model.number="overrideForm.scope_id" type="number" class="text-sm" />
                            <p v-if="overrideForm.errors.scope_id" class="text-xs text-error mt-1">
                                {{ overrideForm.errors.scope_id }}
                            </p>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm text-body-1 cursor-pointer">
                                <input v-model="overrideForm.enabled" type="checkbox" class="rounded" />
                                Enabled for this scope
                            </label>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Reason (optional)</label>
                            <LogerInput
                                v-model="overrideForm.reason"
                                placeholder="Beta tester, support case #123..."
                                class="text-sm"
                            />
                        </div>
                        <LogerButton
                            variant="primary"
                            class="w-full"
                            @click="submitOverride"
                            :processing="overrideForm.processing"
                            :disabled="!overrideForm.scope_id"
                        >
                            Save override
                        </LogerButton>
                    </div>
                </div>

                <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70 mb-3">
                        Active overrides ({{ overrides.length }})
                    </h3>
                    <ul v-if="overrides.length" class="divide-y divide-base">
                        <li v-for="ov in overrides" :key="ov.id" class="py-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-body truncate">
                                        {{ ov.scope_type }} · {{ ov.scope_label }}
                                    </div>
                                    <div v-if="ov.scope_email" class="text-xs text-body-1/60 truncate">
                                        {{ ov.scope_email }}
                                    </div>
                                    <div v-if="ov.reason" class="text-xs text-body-1/70 mt-1 italic">
                                        {{ ov.reason }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span
                                        class="text-xs font-medium px-2 py-0.5 rounded-full"
                                        :class="ov.enabled
                                            ? 'bg-success/15 text-success'
                                            : 'bg-base-lvl-2 text-body-1'"
                                    >
                                        {{ ov.enabled ? 'ON' : 'OFF' }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="removeOverride(ov.id)"
                                        class="text-xs text-error hover:underline"
                                    >
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-xs text-body-1/60 py-3 text-center">
                        No overrides yet. Every user follows the global default.
                    </p>
                </div>
            </aside>
        </div>

        <ConfirmationModal
            :show="showDeleteModal"
            title="Delete this feature flag?"
            @close="showDeleteModal = false"
        >
            <template #content>
                <p>You're about to delete <code class="text-primary">{{ flag.key }}</code>.</p>
                <p class="mt-2 text-sm text-body-1/70">
                    Consumers checking this key will get <code>false</code> from now on.
                    Soft-deleted, so the record survives — but the app will behave as if the flag is off.
                </p>
            </template>
            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <LogerButton variant="neutral" @click="showDeleteModal = false">
                        Cancel
                    </LogerButton>
                    <LogerButton variant="error" @click="confirmDelete" :processing="deleteForm.processing">
                        Delete flag
                    </LogerButton>
                </div>
            </template>
        </ConfirmationModal>
    </AdminTemplate>
</template>
