<script setup lang="ts">
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

import AppLayout from "@/Components/templates/AppLayout.vue";
import SettingsSectionNav from "@/Components/templates/SettingsSectionNav.vue";

/**
 * Settings hub — landing page for /settings. Adapted from the ecf-cloud
 * pattern: grouped cards that link into the actual sub-settings pages,
 * so users see everything Loger can be configured on in one glance
 * instead of guessing URLs. Previously the "Settings" link in the
 * header menu jumped straight to /user/profile — good if profile is
 * what you wanted, terrible if you were looking for messaging integrations
 * or the mail driver.
 *
 * Admin cards (Users, Teams, Feature Flags, Mail) only render when the
 * current user has role admin | super_admin. Regular users don't even
 * see the Administration group.
 */

interface SettingsItem {
    name: string;
    description: string;
    href: string;
    icon: string;
    external?: boolean;
    adminOnly?: boolean;
}

interface SettingsGroup {
    name: string;
    description: string;
    items: SettingsItem[];
    adminOnly?: boolean;
}

const page = usePage();
const user = computed<any>(() => page.props.auth?.user ?? {});
const currentTeamId = computed<number | null>(() => user.value?.current_team_id ?? null);
const isAdmin = computed<boolean>(() =>
    ["admin", "super_admin"].includes(user.value?.role ?? "")
);

const groups = computed<SettingsGroup[]>(() => {
    const all: SettingsGroup[] = [
        {
            name: "Account",
            description: "Your personal profile, security, and API access.",
            items: [
                {
                    name: "Profile",
                    description: "Name, avatar, email address, language.",
                    href: "/user/profile",
                    icon: "fas fa-user",
                },
                {
                    name: "Password & 2FA",
                    description: "Change password, enable two-factor auth.",
                    href: "/user/profile#update-password",
                    icon: "fas fa-lock",
                },
                {
                    name: "API tokens",
                    description: "Create tokens for scripts and third-party apps.",
                    href: "/user/api-tokens",
                    icon: "fas fa-key",
                },
            ],
        },
        {
            name: "Team",
            description: "Manage your household team, members, and shared settings.",
            items: [
                {
                    name: "Team settings",
                    description: "Team name, invite members, transfer ownership.",
                    href: currentTeamId.value ? `/teams/${currentTeamId.value}` : "/teams",
                    icon: "fas fa-users",
                },
                {
                    name: "Modules",
                    description: "Enable pillars: Housing, Meals, Family.",
                    href: "/user/preferences#modules",
                    icon: "fas fa-th-large",
                },
            ],
        },
        {
            name: "Integrations",
            description: "Connect Loger to Gmail, Zen, and your family channels.",
            items: [
                {
                    name: "Connected services",
                    description: "OAuth connections — Gmail, Google Calendar, Zen.",
                    href: "/integrations",
                    icon: "fas fa-plug",
                },
                {
                    name: "Messaging",
                    description: "WhatsApp and Telegram notifications.",
                    href: "/settings/integrations/social",
                    icon: "fab fa-whatsapp",
                },
            ],
        },
        {
            name: "Administration",
            description: "Instance-wide management. Requires admin role.",
            adminOnly: true,
            items: [
                {
                    name: "Overview",
                    description: "Users, teams, and 30-day activity trends.",
                    href: "/admin",
                    icon: "fas fa-chart-line",
                    adminOnly: true,
                },
                {
                    name: "Users",
                    description: "List every user, change roles, impersonate.",
                    href: "/admin/users",
                    icon: "fas fa-users-cog",
                    adminOnly: true,
                },
                {
                    name: "Teams",
                    description: "All teams, their members, and lifecycle.",
                    href: "/admin/teams",
                    icon: "fas fa-building",
                    adminOnly: true,
                },
                {
                    name: "Feature flags",
                    description: "Create flags, toggle globally, add overrides.",
                    href: "/admin/feature-flags",
                    icon: "fas fa-toggle-on",
                    adminOnly: true,
                },
                {
                    name: "Mail driver",
                    description: "SMTP / Mailgun / SES for outgoing email.",
                    href: "/admin/mail",
                    icon: "fas fa-envelope",
                    adminOnly: true,
                },
            ],
        },
        {
            name: "Help & Info",
            description: "Learn how Loger works and get support.",
            items: [
                {
                    name: "About Loger",
                    description: "Version, pillars, tech stack, credits.",
                    href: "/settings/about",
                    icon: "fas fa-info-circle",
                },
                {
                    name: "Help center",
                    description: "Onboarding checklist, FAQ, tutorials.",
                    href: "/settings/help",
                    icon: "fas fa-question-circle",
                },
            ],
        },
    ];

    // Filter groups/items by admin role. Regular users never see the
    // Administration group; admin items in other groups (there are
    // none today, but the flag exists for future ones) are also hidden.
    return all
        .filter((g) => !g.adminOnly || isAdmin.value)
        .map((g) => ({
            ...g,
            items: g.items.filter((it) => !it.adminOnly || isAdmin.value),
        }))
        .filter((g) => g.items.length > 0);
});

const appVersion = computed<string>(
    () => (page.props.version as string) ?? "dev"
);
</script>

<template>
    <AppLayout :title="$t('Settings')">
        <template #header>
            <SettingsSectionNav />
        </template>

        <main class="px-3 mx-auto mt-12 mb-10 max-w-6xl sm:px-6 lg:px-8">
            <header class="mb-6">
                <h2 class="text-2xl font-bold text-body">{{ $t('Settings') }}</h2>
                <p class="text-sm text-body-1 mt-1">
                    {{ $t('Configure Loger for you and your household.') }}
                </p>
            </header>

            <div class="space-y-6">
                <section
                    v-for="group in groups"
                    :key="group.name"
                    class="rounded-lg bg-base-lvl-3 border border-base p-5"
                >
                    <header class="mb-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wide text-body-1/70">
                            {{ $t(group.name) }}
                        </h3>
                        <p class="text-xs text-body-1/60 mt-1">
                            {{ $t(group.description) }}
                        </p>
                    </header>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        <Link
                            v-for="item in group.items"
                            :key="item.name"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-md border border-base bg-base-lvl-2 px-4 py-3 hover:border-primary hover:bg-primary/5 transition-colors group"
                        >
                            <i
                                :class="[item.icon, 'w-8 text-lg text-body-1/60 group-hover:text-primary transition-colors']"
                                aria-hidden="true"
                            />
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-body group-hover:text-primary transition-colors">
                                    {{ $t(item.name) }}
                                </div>
                                <div class="text-xs text-body-1/60 truncate">
                                    {{ $t(item.description) }}
                                </div>
                            </div>
                            <i class="fas fa-chevron-right text-xs text-body-1/40 group-hover:text-primary transition-colors shrink-0" />
                        </Link>
                    </div>
                </section>

                <!-- System info footer -->
                <footer class="text-xs text-body-1/50 text-center py-4">
                    Loger · v{{ appVersion }}
                </footer>
            </div>
        </main>
    </AppLayout>
</template>
