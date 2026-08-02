<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
import AdminTemplate from "./Partials/AdminTemplate.vue";

/**
 * Admin overview dashboard. Three stat cards + two recent-activity
 * lists (users, teams). Deliberately sparse — power admin activities
 * live on their own routes; this is the "what changed this week" surface.
 */

defineProps<{
    stats: {
        users: { total: number; last30: number; trend: number };
        teams: { total: number; last30: number; trend: number };
        admins: number;
    };
    recentUsers: Array<{
        id: number;
        name: string;
        email: string;
        role: string;
        created_at: string;
    }>;
    recentTeams: Array<{
        id: number;
        name: string;
        user_id: number;
        created_at: string;
        owner: { id: number; name: string; email: string } | null;
    }>;
}>();

const formatTrend = (n: number) => `${n > 0 ? "+" : ""}${n}%`;
const trendClass = (n: number) =>
    n > 0 ? "text-success" : n < 0 ? "text-error" : "text-body-1/60";
</script>

<template>
    <AdminTemplate title="Admin Overview">
        <!-- Stat cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                    Total users
                </div>
                <div class="mt-2 text-3xl font-bold text-body tabular-nums">
                    {{ stats.users.total.toLocaleString() }}
                </div>
                <div class="mt-2 text-xs text-body-1/70">
                    +{{ stats.users.last30 }} last 30d
                    <span :class="trendClass(stats.users.trend)" class="ml-2 font-medium">
                        {{ formatTrend(stats.users.trend) }}
                    </span>
                </div>
            </div>

            <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                    Total teams
                </div>
                <div class="mt-2 text-3xl font-bold text-body tabular-nums">
                    {{ stats.teams.total.toLocaleString() }}
                </div>
                <div class="mt-2 text-xs text-body-1/70">
                    +{{ stats.teams.last30 }} last 30d
                    <span :class="trendClass(stats.teams.trend)" class="ml-2 font-medium">
                        {{ formatTrend(stats.teams.trend) }}
                    </span>
                </div>
            </div>

            <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <div class="text-xs font-semibold uppercase tracking-wide text-body-1/60">
                    Staff (admin + super admin)
                </div>
                <div class="mt-2 text-3xl font-bold text-body tabular-nums">
                    {{ stats.admins.toLocaleString() }}
                </div>
                <div class="mt-2 text-xs text-body-1/70">
                    <Link href="/admin/users?role=admin" class="text-primary hover:underline">
                        Manage staff →
                    </Link>
                </div>
            </div>
        </section>

        <!-- Recent activity -->
        <section class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <header class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70">
                        Recent users
                    </h3>
                    <Link href="/admin/users" class="text-xs text-primary hover:underline">
                        View all →
                    </Link>
                </header>
                <ul class="divide-y divide-base">
                    <li v-for="user in recentUsers" :key="user.id" class="py-2 flex items-center justify-between">
                        <Link :href="`/admin/users/${user.id}`" class="flex-1 min-w-0 hover:text-primary">
                            <div class="text-sm font-medium text-body truncate">{{ user.name }}</div>
                            <div class="text-xs text-body-1/60 truncate">{{ user.email }}</div>
                        </Link>
                        <span
                            v-if="user.role !== 'user'"
                            class="ml-2 text-xs font-medium px-2 py-0.5 rounded-full bg-primary/10 text-primary shrink-0"
                        >
                            {{ user.role }}
                        </span>
                    </li>
                    <li v-if="!recentUsers.length" class="py-4 text-sm text-body-1/60 text-center">
                        No users yet.
                    </li>
                </ul>
            </div>

            <div class="rounded-lg bg-base-lvl-3 border border-base p-5">
                <header class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70">
                        Recent teams
                    </h3>
                    <Link href="/admin/teams" class="text-xs text-primary hover:underline">
                        View all →
                    </Link>
                </header>
                <ul class="divide-y divide-base">
                    <li v-for="team in recentTeams" :key="team.id" class="py-2 flex items-center justify-between">
                        <Link :href="`/admin/teams/${team.id}`" class="flex-1 min-w-0 hover:text-primary">
                            <div class="text-sm font-medium text-body truncate">{{ team.name }}</div>
                            <div class="text-xs text-body-1/60 truncate">
                                by {{ team.owner?.name ?? 'unknown' }}
                            </div>
                        </Link>
                    </li>
                    <li v-if="!recentTeams.length" class="py-4 text-sm text-body-1/60 text-center">
                        No teams yet.
                    </li>
                </ul>
            </div>
        </section>
    </AdminTemplate>
</template>
