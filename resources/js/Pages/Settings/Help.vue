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
const appVersion = computed(() => page.props.version ?? '');
const versionLabel = computed(() => {
    const v = appVersion.value?.toString() ?? '';
    return v.startsWith('v') ? v : `v${v}`;
});

type TabId = 'started' | 'faq' | 'tutorials' | 'contact';
const activeTab = ref<TabId>('started');

const tabs: { id: TabId; label: string }[] = [
    { id: 'started', label: 'Get started' },
    { id: 'faq', label: 'Common questions' },
    { id: 'tutorials', label: 'Tutorials' },
    { id: 'contact', label: 'Get help' },
];

// First-run checklist — what a new user should do in their first 10 minutes.
// Order matches the marketing pillar order: Finance leads (it's how people
// arrive), Calendar follows (the integrating-magic moment).
const gettingStarted = [
    {
        n: 1,
        title: 'Add your first account',
        body: 'Pick the bank or wallet you use most. You can add as many as you like, in any currency — DOP, USD, EUR, MXN, all native.',
        cta: { label: 'Add an account', href: '/finance/accounts/create' },
    },
    {
        n: 2,
        title: 'Set up your first budget month',
        body: 'YNAB-style envelope budgeting. Assign every dollar (or peso) a job. You can edit categories any time.',
        cta: { label: 'Open Budget', href: '/finance/budgets' },
    },
    {
        n: 3,
        title: 'Import a bank statement',
        body: 'Drop in a PDF — BHD, Banreservas, or any other. Loger reads it and turns it into transactions you can review and approve.',
        cta: { label: 'Import a statement', href: '/finance/transactions?filter[status]=draft' },
    },
    {
        n: 4,
        title: 'Enable the pillars you need',
        body: 'Start with Finance and Calendar. Add Food, Home, and Family when you\'re ready. Each pillar can be turned on or off any time.',
        cta: { label: 'Manage modules', href: '/settings/modules' },
    },
    {
        n: 5,
        title: 'Invite your household',
        body: 'Loger is for the whole family — share access with your partner or roommates. Each person gets their own profile.',
        cta: { label: 'Team settings', href: '/settings/team' },
    },
];

// FAQ — same six questions as the marketing landing, kept in sync so a user
// who saw them before signup gets the same answers in-app.
const faqs = [
    {
        q: 'What if my bank isn\'t supported?',
        a: 'All banks are supported. Loger doesn\'t depend on bank APIs — drop in a PDF statement or enter transactions manually. We don\'t connect to your bank, so we don\'t even see credentials.',
    },
    {
        q: 'Is my financial data safe?',
        a: 'Your data stays in your account, encrypted at rest, never sold. The engine is open source on GitHub, so you can read exactly what Loger does with your data — or self-host it if you\'d rather not trust us at all.',
    },
    {
        q: 'Can I use Loger on my phone?',
        a: 'Yes — Loger is a responsive web app that works on any device. You can add it to your home screen on iOS and Android for an app-like experience.',
    },
    {
        q: 'What happens if I cancel Plus?',
        a: 'You keep all your data and downgrade to Free. Nothing is locked behind the paywall except the DR-specific bank parsing and a few automation features.',
    },
    {
        q: 'Do I need all five pillars?',
        a: 'No — enable only what your household uses. Most people start with Finance and add Calendar.',
    },
    {
        q: 'Why am I getting daily notifications about a bill I already paid?',
        a: 'If a planned bill\'s notification keeps firing, mark it as paid from the notification card or from the planner detail. Loger normally auto-matches a real transaction back to the planned one — when that doesn\'t happen (different category, different month), the manual "Mark as paid" button stops the reminders.',
    },
];

const helpResources = [
    {
        icon: '✉️',
        title: 'Email support',
        body: 'jesusant.guerrero@gmail.com — replies are personal, usually within 24 hours.',
        href: 'mailto:jesusant.guerrero@gmail.com?subject=Loger%20support',
        external: false,
    },
    {
        icon: '🐛',
        title: 'Report a bug',
        body: 'GitHub issues on the Atmosphere repo. Include screenshots and steps to reproduce.',
        href: 'https://github.com/jesusantguerrero/atmosphere/issues/new?labels=bug',
        external: true,
    },
    {
        icon: '✨',
        title: 'Request a feature',
        body: 'Open an issue with the "feature" label. Tell us the problem you\'re trying to solve.',
        href: 'https://github.com/jesusantguerrero/atmosphere/issues/new?labels=feature',
        external: true,
    },
    {
        icon: '📚',
        title: 'Documentation',
        body: 'Setup guides, self-hosting instructions, and developer reference.',
        href: 'https://jesusantguerrero.github.io/atmosphere',
        external: true,
    },
];

const openFaqIndex = ref<number | null>(0);
const toggleFaq = (i: number) => {
    openFaqIndex.value = openFaqIndex.value === i ? null : i;
};
</script>

<template>
    <AppLayout title="Settings - Help">
        <template #header>
            <SettingsSectionNav />
        </template>

        <div class="h-auto py-12 pt-32 mx-auto space-y-4 max-w-4xl sm:px-6 lg:px-8">

            <section class="rounded-xl bg-base-lvl-3 p-8 lg:p-10 border border-base-lvl-2">
                <header class="flex flex-col items-center text-center">
                    <AppIcon size="medium" />
                    <h1 class="mt-4 text-2xl font-bold text-body">Help Center</h1>
                    <p class="mt-2 text-sm text-body-1/70 max-w-md">
                        Setup help, common questions, and how to reach a human when you need one.
                    </p>
                    <span class="mt-4 px-3 py-1 rounded-full bg-base-lvl-2 text-body-1 font-mono text-xs">
                        {{ versionLabel }}
                    </span>
                </header>

                <!-- Tab strip -->
                <nav class="mt-8 flex flex-wrap items-center justify-center gap-1 border-b border-base-lvl-2 -mb-px">
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

                <!-- ─── Get started tab ─────────────────────────────────── -->
                <div v-if="activeTab === 'started'" class="mt-8 space-y-4">
                    <p class="text-sm text-body-1/70 leading-relaxed mb-6">
                        Five steps to a household running on Loger. You don't have to do them in order — but most people find this is the fastest path.
                    </p>

                    <ol class="space-y-3">
                        <li
                            v-for="step in gettingStarted"
                            :key="step.n"
                            class="flex items-start gap-4 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2"
                        >
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-primary/15 text-primary flex items-center justify-center text-sm font-bold" aria-hidden="true">
                                {{ step.n }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-body">{{ step.title }}</div>
                                <div class="text-xs text-body-1/70 mt-1 leading-relaxed">{{ step.body }}</div>
                                <a
                                    :href="step.cta.href"
                                    class="inline-flex items-center gap-1 mt-2 text-xs font-medium text-primary hover:underline"
                                >
                                    {{ step.cta.label }} →
                                </a>
                            </div>
                        </li>
                    </ol>
                </div>

                <!-- ─── FAQ tab ─────────────────────────────────────────── -->
                <div v-else-if="activeTab === 'faq'" class="mt-8 space-y-3">
                    <div
                        v-for="(item, i) in faqs"
                        :key="item.q"
                        class="rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 overflow-hidden"
                    >
                        <button
                            type="button"
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 text-left text-sm font-semibold text-body hover:bg-base-lvl-2 transition-colors"
                            @click="toggleFaq(i)"
                            :aria-expanded="openFaqIndex === i"
                        >
                            <span>{{ item.q }}</span>
                            <svg
                                class="w-4 h-4 text-body-1/60 flex-shrink-0 transition-transform"
                                :class="{ 'rotate-180': openFaqIndex === i }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-if="openFaqIndex === i" class="px-4 pb-4 text-sm text-body-1/80 leading-relaxed">
                            {{ item.a }}
                        </div>
                    </div>
                </div>

                <!-- ─── Tutorials tab ───────────────────────────────────── -->
                <div v-else-if="activeTab === 'tutorials'" class="mt-8 space-y-4">
                    <div class="flex flex-col items-center justify-center text-center py-8 px-6 rounded-lg bg-base-lvl-2/40 border border-dashed border-base-lvl-2">
                        <span class="text-3xl mb-3" aria-hidden="true">🎬</span>
                        <h3 class="text-sm font-semibold text-body">Video tutorials are coming</h3>
                        <p class="text-xs text-body-1/70 mt-1 max-w-sm">
                            Short walkthroughs of the things people most often ask about — multi-currency setup, PDF imports, the meal-planner-to-budget flow.
                        </p>
                        <a
                            href="https://jesusantguerrero.github.io/atmosphere"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="mt-4 text-xs font-medium text-primary hover:underline"
                        >
                            In the meantime, browse the docs →
                        </a>
                    </div>

                    <p class="text-xs text-body-1/60 italic text-center pt-2">
                        Have a topic you want covered? <a href="mailto:jesusant.guerrero@gmail.com?subject=Loger%20tutorial%20request" class="text-primary hover:underline">Tell us</a>.
                    </p>
                </div>

                <!-- ─── Get help tab ────────────────────────────────────── -->
                <div v-else-if="activeTab === 'contact'" class="mt-8 space-y-3">
                    <a
                        v-for="resource in helpResources"
                        :key="resource.title"
                        :href="resource.href"
                        :target="resource.external ? '_blank' : undefined"
                        :rel="resource.external ? 'noopener noreferrer' : undefined"
                        class="flex items-start gap-3 p-4 rounded-lg bg-base-lvl-2/40 border border-base-lvl-2 hover:border-primary transition-colors"
                    >
                        <span class="text-2xl leading-none flex-shrink-0" aria-hidden="true">{{ resource.icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-body">{{ resource.title }}</div>
                            <div class="text-xs text-body-1/70 mt-0.5 leading-relaxed">{{ resource.body }}</div>
                        </div>
                        <svg v-if="resource.external" class="w-3 h-3 text-body-1/40 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>

                    <p class="text-xs text-body-1/60 italic leading-relaxed text-center pt-4 border-t border-base-lvl-2/60 mt-2">
                        Loger is a small, solo project. Replies are personal, not from a queue.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
