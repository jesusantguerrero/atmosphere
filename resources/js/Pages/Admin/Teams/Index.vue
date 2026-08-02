<script setup lang="ts">
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

/**
 * Admin · Teams list. Same debounced search pattern as Users/Index —
 * types show count in the header, tap through to detail for members.
 */

interface TeamRow {
    id: number;
    name: string;
    personal_team?: boolean;
    users_count: number;
    created_at: string;
    owner: { id: number; name: string; email: string } | null;
}

interface PaginatedTeams {
    data: TeamRow[];
    current_page: number;
    last_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    teams: PaginatedTeams;
    filters: { search: string };
}>();

const search = ref(props.filters.search ?? "");
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            "/admin/teams",
            { search: value },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 200);
});
</script>

<template>
    <AdminTemplate title="Teams">
        <section class="flex flex-wrap items-center gap-2 mb-4">
            <div class="w-full sm:w-72">
                <LogerInput v-model="search" placeholder="Search by team name" class="text-sm" />
            </div>
            <div class="ml-auto text-xs text-body-1/60">
                {{ teams.total.toLocaleString() }} total
            </div>
        </section>

        <div class="rounded-lg bg-base-lvl-3 border border-base overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wide text-body-1/60 bg-base-lvl-2">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">Team</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">Owner</th>
                        <th class="text-right px-4 py-3 font-semibold">Members</th>
                        <th class="text-left px-4 py-3 font-semibold hidden lg:table-cell">Created</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base">
                    <tr
                        v-for="team in teams.data"
                        :key="team.id"
                        class="hover:bg-base-lvl-2/50 transition-colors"
                    >
                        <td class="px-4 py-3">
                            <Link :href="`/admin/teams/${team.id}`" class="font-medium text-body hover:text-primary">
                                {{ team.name }}
                            </Link>
                            <span
                                v-if="team.personal_team"
                                class="ml-2 text-xs px-1.5 py-0.5 rounded bg-base-lvl-2 text-body-1/70"
                            >
                                personal
                            </span>
                        </td>
                        <td class="px-4 py-3 text-body-1 hidden md:table-cell">
                            <span v-if="team.owner">
                                {{ team.owner.name }}
                                <span class="text-xs text-body-1/60">· {{ team.owner.email }}</span>
                            </span>
                            <span v-else class="text-body-1/40">—</span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium">
                            {{ team.users_count }}
                        </td>
                        <td class="px-4 py-3 text-body-1 hidden lg:table-cell">
                            {{ new Date(team.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/admin/teams/${team.id}`" class="text-primary hover:underline text-xs">
                                Open →
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!teams.data.length">
                        <td colspan="5" class="px-4 py-12 text-center text-body-1/60">
                            No teams match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="teams.last_page > 1" class="flex items-center justify-center gap-1 mt-4 text-sm">
            <Link
                v-for="link in teams.links"
                :key="link.label"
                :href="link.url ?? ''"
                v-html="link.label"
                class="px-3 py-1.5 rounded-md"
                :class="[
                    link.active
                        ? 'bg-primary text-white font-medium'
                        : link.url
                            ? 'text-body-1 hover:bg-base-lvl-2'
                            : 'text-body-1/40 pointer-events-none',
                ]"
                preserve-scroll
                preserve-state
            />
        </nav>
    </AdminTemplate>
</template>
