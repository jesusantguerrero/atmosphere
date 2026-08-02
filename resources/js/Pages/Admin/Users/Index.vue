<script setup lang="ts">
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AdminTemplate from "../Partials/AdminTemplate.vue";
import LogerInput from "@/Components/atoms/LogerInput.vue";

/**
 * Admin · Users list. Paginated table with search + role filter.
 * The filters use `router.get` with `preserveState` so typing doesn't
 * blow away the input focus. Search is debounced client-side (200ms)
 * to avoid a request per keystroke.
 */

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: "user" | "admin" | "super_admin";
    email_verified_at: string | null;
    created_at: string;
}

interface PaginatedUsers {
    data: UserRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: { search: string; role: string | null };
}>();

const search = ref(props.filters.search ?? "");
const role = ref<string | null>(props.filters.role ?? null);

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
watch(search, (value) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            "/admin/users",
            { search: value, role: role.value ?? undefined },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 200);
});

watch(role, (value) => {
    router.get(
        "/admin/users",
        { search: search.value, role: value ?? undefined },
        { preserveState: true, preserveScroll: true, replace: true }
    );
});

const roleBadgeClass = (r: string) => {
    if (r === "super_admin") return "bg-primary text-white";
    if (r === "admin") return "bg-primary/15 text-primary";
    return "bg-base-lvl-2 text-body-1";
};
</script>

<template>
    <AdminTemplate title="Users">
        <!-- Toolbar -->
        <section class="flex flex-wrap items-center gap-2 mb-4">
            <div class="w-full sm:w-72">
                <LogerInput
                    v-model="search"
                    placeholder="Search by name or email"
                    class="text-sm"
                />
            </div>
            <select
                v-model="role"
                class="px-3 py-2 text-sm rounded-md bg-base-lvl-3 border border-base text-body focus:outline-none focus:border-primary"
            >
                <option :value="null">All roles</option>
                <option value="user">User</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super admin</option>
            </select>
            <div class="ml-auto text-xs text-body-1/60">
                {{ users.total.toLocaleString() }} total
            </div>
        </section>

        <!-- Table -->
        <div class="rounded-lg bg-base-lvl-3 border border-base overflow-hidden">
            <table class="w-full text-sm">
                <thead class="text-xs uppercase tracking-wide text-body-1/60 bg-base-lvl-2">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold">User</th>
                        <th class="text-left px-4 py-3 font-semibold hidden md:table-cell">Email</th>
                        <th class="text-left px-4 py-3 font-semibold">Role</th>
                        <th class="text-left px-4 py-3 font-semibold hidden lg:table-cell">Verified</th>
                        <th class="text-left px-4 py-3 font-semibold hidden lg:table-cell">Joined</th>
                        <th class="w-8"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-base">
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="hover:bg-base-lvl-2/50 transition-colors"
                    >
                        <td class="px-4 py-3">
                            <Link :href="`/admin/users/${user.id}`" class="font-medium text-body hover:text-primary">
                                {{ user.name }}
                            </Link>
                            <div class="text-xs text-body-1/60 md:hidden">{{ user.email }}</div>
                        </td>
                        <td class="px-4 py-3 text-body-1 hidden md:table-cell">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :class="roleBadgeClass(user.role)"
                            >
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-body-1 hidden lg:table-cell">
                            <span v-if="user.email_verified_at" class="text-success">✓</span>
                            <span v-else class="text-body-1/40">—</span>
                        </td>
                        <td class="px-4 py-3 text-body-1 hidden lg:table-cell">
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link :href="`/admin/users/${user.id}`" class="text-primary hover:underline text-xs">
                                Open →
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!users.data.length">
                        <td colspan="6" class="px-4 py-12 text-center text-body-1/60">
                            No users match your filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav v-if="users.last_page > 1" class="flex items-center justify-center gap-1 mt-4 text-sm">
            <Link
                v-for="link in users.links"
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
