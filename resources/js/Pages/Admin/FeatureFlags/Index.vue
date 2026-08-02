<script setup lang="ts">
import { ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import LogerButton from "@/Components/atoms/LogerButton.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";
import Modal from "@/Components/atoms/Modal.vue";

/**
 * Admin · Feature Flags list. Each row shows the flag key, category
 * pill (color-coded), global default toggle, override count, and a
 * link to the detail page for per-scope overrides + rollout tuning.
 *
 * "Create flag" opens an inline modal — kept in the same page so the
 * list refreshes without a full page navigation on success.
 */

interface FlagRow {
    id: number;
    key: string;
    name: string;
    description: string | null;
    scope: "global" | "team" | "user";
    category: "experimental" | "gating" | "kill_switch" | "operations";
    enabled_by_default: boolean;
    rollout_percentage: number;
    overrides_count: number;
    created_at: string;
}

defineProps<{ flags: FlagRow[] }>();

const categoryClass = (c: string) => {
    switch (c) {
        case "kill_switch": return "bg-error/15 text-error";
        case "gating": return "bg-warning/15 text-warning";
        case "operations": return "bg-secondary/15 text-secondary";
        default: return "bg-base-lvl-2 text-body-1";
    }
};

const toggleGlobal = (flag: FlagRow) => {
    router.patch(
        `/admin/feature-flags/${flag.id}`,
        { enabled_by_default: !flag.enabled_by_default },
        { preserveScroll: true }
    );
};

const showCreateModal = ref(false);
const createForm = useForm({
    key: "",
    name: "",
    description: "",
    scope: "global",
    category: "experimental",
    enabled_by_default: false,
});
const submitCreate = () => {
    createForm.post("/admin/feature-flags", {
        onSuccess: () => {
            createForm.reset();
            showCreateModal.value = false;
        },
    });
};
</script>

<template>
    <AdminTemplate title="Feature Flags">
        <section class="flex items-center justify-between mb-4">
            <p class="text-sm text-body-1/70">
                Runtime toggles. Flags default off until enabled.
                <span class="text-body-1/50">
                    · {{ flags.length }} total
                </span>
            </p>
            <LogerButton variant="primary" @click="showCreateModal = true">
                <IMdiPlus class="mr-1" /> New flag
            </LogerButton>
        </section>

        <!-- Flags list -->
        <div class="rounded-lg bg-base-lvl-3 border border-base overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wide text-body-1/60 bg-base-lvl-2">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">Flag</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">Category</th>
                        <th class="text-center px-4 py-3 font-semibold">Global</th>
                        <th class="text-right px-4 py-3 font-semibold hidden md:table-cell">Rollout</th>
                        <th class="text-right px-4 py-3 font-semibold hidden lg:table-cell">Overrides</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base">
                    <tr
                        v-for="flag in flags"
                        :key="flag.id"
                        class="hover:bg-base-lvl-2/50 transition-colors"
                    >
                        <td class="px-4 py-3">
                            <Link :href="`/admin/feature-flags/${flag.id}`" class="font-medium text-body hover:text-primary">
                                {{ flag.name }}
                            </Link>
                            <div class="text-xs text-body-1/60 font-mono">{{ flag.key }}</div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="categoryClass(flag.category)"
                            >
                                {{ flag.category }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                type="button"
                                @click="toggleGlobal(flag)"
                                class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium transition-colors"
                                :class="flag.enabled_by_default
                                    ? 'bg-success/15 text-success hover:bg-success/25'
                                    : 'bg-base-lvl-2 text-body-1 hover:bg-base-lvl-1'"
                            >
                                <span class="w-2 h-2 rounded-full" :class="flag.enabled_by_default ? 'bg-success' : 'bg-body-1/40'" />
                                {{ flag.enabled_by_default ? 'ON' : 'OFF' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right hidden md:table-cell tabular-nums">
                            <span :class="flag.rollout_percentage > 0 ? 'text-body' : 'text-body-1/40'">
                                {{ flag.rollout_percentage }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right hidden lg:table-cell tabular-nums">
                            <span :class="flag.overrides_count > 0 ? 'text-body' : 'text-body-1/40'">
                                {{ flag.overrides_count }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/admin/feature-flags/${flag.id}`" class="text-primary hover:underline text-xs">
                                Open →
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!flags.length">
                        <td colspan="6" class="px-4 py-12 text-center text-body-1/60">
                            No feature flags yet. Create one to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create modal -->
        <Modal :show="showCreateModal" max-width="lg" @close="showCreateModal = false">
            <section class="p-6 bg-base-lvl-3 text-body">
                <h2 class="text-lg font-semibold mb-4">New feature flag</h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-body-1 mb-1">
                            Key <span class="text-body-1/60">(kebab-case, unique)</span>
                        </label>
                        <LogerInput
                            v-model="createForm.key"
                            placeholder="new-billing-flow"
                            class="text-sm font-mono"
                        />
                        <p v-if="createForm.errors.key" class="text-xs text-error mt-1">
                            {{ createForm.errors.key }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-body-1 mb-1">Display name</label>
                        <LogerInput v-model="createForm.name" placeholder="New billing flow" class="text-sm" />
                        <p v-if="createForm.errors.name" class="text-xs text-error mt-1">
                            {{ createForm.errors.name }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-body-1 mb-1">Description</label>
                        <textarea
                            v-model="createForm.description"
                            rows="2"
                            class="w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                            placeholder="What does this flag gate? Who's it for?"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Scope</label>
                            <select
                                v-model="createForm.scope"
                                class="w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                            >
                                <option value="global">Global</option>
                                <option value="team">Team</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-body-1 mb-1">Category</label>
                            <select
                                v-model="createForm.category"
                                class="w-full px-3 py-2 text-sm rounded-md bg-base-lvl-2 border border-base text-body focus:outline-none focus:border-primary"
                            >
                                <option value="experimental">Experimental</option>
                                <option value="gating">Gating</option>
                                <option value="kill_switch">Kill switch</option>
                                <option value="operations">Operations</option>
                            </select>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-body-1 cursor-pointer">
                        <input v-model="createForm.enabled_by_default" type="checkbox" class="rounded" />
                        Enable by default (all users get this immediately)
                    </label>
                </div>

                <footer class="flex items-center justify-end gap-2 mt-6 pt-4 border-t border-base">
                    <LogerButton variant="neutral" @click="showCreateModal = false">
                        Cancel
                    </LogerButton>
                    <LogerButton
                        variant="primary"
                        @click="submitCreate"
                        :processing="createForm.processing"
                        :disabled="!createForm.key || !createForm.name"
                    >
                        Create flag
                    </LogerButton>
                </footer>
            </section>
        </Modal>
    </AdminTemplate>
</template>
