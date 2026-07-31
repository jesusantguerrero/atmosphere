<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import AppLayout from '@/Components/templates/AppLayout.vue';
import SettingsSectionNav from '@/Components/templates/SettingsSectionNav.vue';
import AppIcon from '@/Components/AppIcon.vue';

defineProps({
    services: { type: Array, default: () => [] },
    integrations: { type: Array, default: () => [] },
    recipes: { type: Array, default: () => [] },
    tasks: { type: Array, default: () => [] },
});

const page = usePage();

// Real version comes from config('app.version') via Inertia shared props.
// The hardcoded "1.0.0" + "v2.0.0-alpha.19" mismatch was a bug.
const appVersion = computed(() => page.props.version ?? '');
const environment = computed(() => page.props.environment ?? 'production');
const isPreRelease = computed(() => /alpha|beta|rc/i.test(appVersion.value));

type TabId = 'about' | 'opensource' | 'credits' | 'support';
const activeTab = ref<TabId>('about');

const tabs: { id: TabId; label: string }[] = [
    { id: 'about', label: 'About' },
    { id: 'opensource', label: 'Open Source' },
    { id: 'credits', label: 'Credits' },
    { id: 'support', label: 'Support' },
];

// 5 pillars — same vocabulary as the landing so a user moving between
// the marketing site and the app sees consistent framing.
const pillars = [
    { icon: '💰', name: 'Finance', body: 'Multi-currency envelope budgets, PDF statement import, net worth.' },
    { icon: '📅', name: 'Calendar', body: 'Pulls dated items from every other pillar into one weekly view.' },
    { icon: '🏠', name: 'Home', body: 'Bills, equipment, chores, "when did we last…?" checks.' },
    { icon: '🍽️', name: 'Food', body: 'Recipes, meal plan, pantry, cost-per-recipe.' },
    { icon: '👨‍👩‍👧', name: 'Family', body: 'Profiles, important dates, health notes, gift ideas.' },
];

const techStack = [
    { name: 'Laravel 11', url: 'https://laravel.com' },
    { name: 'Vue 3 + Inertia.js', url: 'https://vuejs.org' },
    { name: 'Tailwind CSS', url: 'https://tailwindcss.com' },
    { name: 'Atmosphere UI', url: 'https://github.com/jesusantguerrero/atmosphere-ui' },
    { name: 'Journal (accounting engine)', url: 'https://github.com/insane-code/journal' },
];
</script>

<template>
    <AppLayout title="Settings - About">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="h-auto py-12 pt-32 mx-auto space-y-4 max-w-4xl sm:px-6 lg:px-8">

            <!-- ─── Brand header ─────────────────────────────────────── -->
            <section class="rounded-xl bg-base-lvl-3 p-8 lg:p-10 border border-base-lvl-2">
                <header class="flex flex-col items-center text-center">
                    <AppIcon size="medium" />
                    <h1 class="mt-4 text-2xl font-bold text-body">The Family Operating System</h1>
                    <p class="mt-2 text-sm text-body-1/70 max-w-md">
                        One app to run the financial, logistical, and relational machinery of a household.
                    </p>

                    <!-- Version + license badges -->
                    <div class="mt-5 flex items-center justify-center gap-3 text-xs">
                        <span class="px-3 py-1 rounded-full bg-base-lvl-2 text-body-1 font-mono">
                            {{ appVersion?.toString().startsWith('v') ? appVersion : `v${appVersion}` }}
                        </span>
                        <span
                            v-if="isPreRelease"
                            class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 font-medium"
                            :title="`Environment: ${environment}`"
                        >
                            Pre-release
                        </span>
                        <a
                            href="https://github.com/jesusantguerrero/atmosphere/blob/master/LICENSE"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="px-3 py-1 rounded-full bg-base-lvl-2 text-body-1 font-medium hover:text-primary transition-colors"
                        >
                            BSD-3 licensed
                        </a>
                    </div>
                </header>

                <!-- Tab strip -->
                <nav class="mt-8 flex flex-wrap items-center justify-center gap-1 border-b border-base-lvl-2 pb-0 -mb-px">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === tab.id
                            ? 'text-primary border-primary'
                            : 'text-body-1/60 border-transparent hover:text-body-1 hover:border-base-lvl-2'"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </nav>

                <!-- ─── About tab ───────────────────────────────────────── -->
                <div v-if="activeTab === 'about'" class="mt-8 space-y-8">
                    <p class="text-body leading-relaxed">
                        Loger pulls budget, meals, chores, family schedules and home maintenance into one system.
                        It works without bank-sync APIs (Plaid, Yodlee, etc.), supports multiple currencies natively,
                        and reaches beyond money into the daily logistics of running a home.
                    </p>

                    <div>
                        <h2 class="text-xs uppercase tracking-wider font-semibold text-body-1/60 mb-3">Five pillars, one system</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div
                                v-for="pillar in pillars"
                                :key="pillar.name"
                                class="flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2"
                            >
                                <span class="text-2xl leading-none" aria-hidden="true">{{ pillar.icon }}</span>
                                <div>
                                    <div class="text-sm font-semibold text-body">{{ pillar.name }}</div>
                                    <div class="text-xs text-body-1/70 mt-0.5">{{ pillar.body }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p class="text-sm text-body-1/70 italic leading-relaxed">
                        Built for households outside the US banking world — where Plaid doesn't exist,
                        the bank is a different one for every family member, and money moves between
                        currencies as a matter of routine.
                    </p>
                </div>

                <!-- ─── Open Source tab ─────────────────────────────────── -->
                <div v-else-if="activeTab === 'opensource'" class="mt-8 space-y-6">
                    <div>
                        <h2 class="text-lg font-semibold text-body mb-2">Loger is hosted Atmosphere.</h2>
                        <p class="text-sm text-body-1/80 leading-relaxed">
                            Atmosphere is the open-source household OS that powers Loger — BSD-3 licensed, on GitHub.
                            Anyone can fork it and self-host. Loger is the distribution we run for you, with bank-aware
                            features and managed updates.
                        </p>
                        <p class="text-sm text-body-1/70 leading-relaxed mt-2 italic">
                            Same code, two ways to use it. Like Chromium and Chrome.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <a
                            href="https://github.com/jesusantguerrero/atmosphere"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 hover:border-primary hover:bg-base-lvl-2 transition-all"
                        >
                            <svg class="w-6 h-6 text-body flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.07 3.29 9.37 7.86 10.89.58.11.79-.25.79-.56v-2.16c-3.2.69-3.88-1.37-3.88-1.37-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.68 1.24 3.34.95.1-.74.4-1.24.72-1.53-2.55-.29-5.24-1.28-5.24-5.71 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.93 10.93 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.44-2.7 5.41-5.27 5.7.41.36.78 1.06.78 2.13v3.16c0 .31.21.67.8.56 4.56-1.52 7.85-5.82 7.85-10.89C23.5 5.65 18.35.5 12 .5z"/></svg>
                            <div>
                                <div class="text-sm font-semibold text-body group-hover:text-primary transition-colors">Atmosphere on GitHub</div>
                                <div class="text-xs text-body-1/70 mt-0.5">Star, fork, or self-host. BSD-3 license.</div>
                            </div>
                        </a>

                        <a
                            href="https://loger.neatlancer.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="group flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 hover:border-primary hover:bg-base-lvl-2 transition-all"
                        >
                            <span class="text-2xl leading-none" aria-hidden="true">🏠</span>
                            <div>
                                <div class="text-sm font-semibold text-body group-hover:text-primary transition-colors">Loger marketing site</div>
                                <div class="text-xs text-body-1/70 mt-0.5">Pricing, demo, and the full product story.</div>
                            </div>
                        </a>
                    </div>

                    <div class="text-xs text-body-1/60 leading-relaxed pt-2 border-t border-base-lvl-2/60">
                        Want to self-host? Clone the repo, follow the install steps in the README, and you're
                        running the same engine Loger does — minus the DR-specific bank-statement parsing,
                        which is the one piece reserved for hosted Plus plans.
                    </div>
                </div>

                <!-- ─── Credits tab ─────────────────────────────────────── -->
                <div v-else-if="activeTab === 'credits'" class="mt-8 space-y-8">
                    <!-- Founder card -->
                    <figure class="flex flex-col sm:flex-row items-center sm:items-start gap-4 p-5 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2">
                        <div class="w-14 h-14 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center text-white font-semibold text-xl shadow-md shadow-primary/30 flex-shrink-0" aria-hidden="true">
                            JG
                        </div>
                        <div class="flex-1 text-center sm:text-left">
                            <div class="text-base font-semibold text-body">Jesus Guerrero</div>
                            <div class="text-xs text-body-1/70 mt-0.5">Solo founder · Built in the Dominican Republic</div>
                            <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-x-3 gap-y-1 text-xs">
                                <a href="https://jesusantguerrero.com" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">jesusantguerrero.com</a>
                                <span class="text-body-1/30" aria-hidden="true">·</span>
                                <a href="https://www.linkedin.com/in/jesus-guerrero-alvarez/" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">LinkedIn</a>
                                <span class="text-body-1/30" aria-hidden="true">·</span>
                                <a href="https://twitter.com/jesusntguerrero" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">Twitter</a>
                                <span class="text-body-1/30" aria-hidden="true">·</span>
                                <a href="https://github.com/jesusantguerrero" target="_blank" rel="noopener noreferrer" class="text-primary hover:underline">GitHub</a>
                            </div>
                        </div>
                    </figure>

                    <!-- Tech stack -->
                    <div>
                        <h2 class="text-xs uppercase tracking-wider font-semibold text-body-1/60 mb-3">Built with</h2>
                        <ul class="space-y-2">
                            <li v-for="item in techStack" :key="item.name" class="flex items-center justify-between text-sm py-1.5">
                                <span class="text-body">{{ item.name }}</span>
                                <a :href="item.url" target="_blank" rel="noopener noreferrer" class="text-xs text-body-1/60 hover:text-primary transition-colors">↗</a>
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-body-1/60 italic leading-relaxed pt-4 border-t border-base-lvl-2/60">
                        Thanks to every household running Loger today — and every developer who's filed an
                        issue, opened a PR, or self-hosted Atmosphere. This software exists because of you.
                    </p>
                </div>

                <!-- ─── Support tab ─────────────────────────────────────── -->
                <div v-else-if="activeTab === 'support'" class="mt-8 space-y-4">
                    <a
                        href="mailto:jesusant.guerrero@gmail.com?subject=Loger%20support"
                        class="flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 hover:border-primary transition-colors"
                    >
                        <span class="text-2xl leading-none" aria-hidden="true">✉️</span>
                        <div>
                            <div class="text-sm font-semibold text-body">Email support</div>
                            <div class="text-xs text-body-1/70 mt-0.5">jesusant.guerrero@gmail.com — usually replies within 24 hours.</div>
                        </div>
                    </a>

                    <a
                        href="https://github.com/jesusantguerrero/atmosphere/issues"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 hover:border-primary transition-colors"
                    >
                        <svg class="w-6 h-6 text-body flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 .5C5.65.5.5 5.65.5 12c0 5.07 3.29 9.37 7.86 10.89.58.11.79-.25.79-.56v-2.16c-3.2.69-3.88-1.37-3.88-1.37-.52-1.32-1.27-1.67-1.27-1.67-1.04-.71.08-.7.08-.7 1.15.08 1.76 1.18 1.76 1.18 1.02 1.75 2.68 1.24 3.34.95.1-.74.4-1.24.72-1.53-2.55-.29-5.24-1.28-5.24-5.71 0-1.26.45-2.29 1.18-3.1-.12-.29-.51-1.46.11-3.04 0 0 .96-.31 3.15 1.18a10.93 10.93 0 0 1 5.74 0c2.19-1.49 3.15-1.18 3.15-1.18.62 1.58.23 2.75.11 3.04.74.81 1.18 1.84 1.18 3.1 0 4.44-2.7 5.41-5.27 5.7.41.36.78 1.06.78 2.13v3.16c0 .31.21.67.8.56 4.56-1.52 7.85-5.82 7.85-10.89C23.5 5.65 18.35.5 12 .5z"/></svg>
                        <div>
                            <div class="text-sm font-semibold text-body">Report a bug or request a feature</div>
                            <div class="text-xs text-body-1/70 mt-0.5">GitHub issues on the Atmosphere repo.</div>
                        </div>
                    </a>

                    <a
                        href="https://jesusantguerrero.github.io/atmosphere"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 hover:border-primary transition-colors"
                    >
                        <span class="text-2xl leading-none" aria-hidden="true">📚</span>
                        <div>
                            <div class="text-sm font-semibold text-body">Documentation</div>
                            <div class="text-xs text-body-1/70 mt-0.5">Setup guides, self-hosting, and developer reference.</div>
                        </div>
                    </a>

                    <p class="text-xs text-body-1/60 italic leading-relaxed pt-4 mt-2 border-t border-base-lvl-2/60">
                        Loger is a small, solo project. Replies are personal, not from a queue.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
