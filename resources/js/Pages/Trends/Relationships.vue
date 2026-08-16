<script setup lang="ts">
import { ref, computed, toRefs } from 'vue';
// @ts-ignore
import { AtDatePager } from 'atmosphere-ui';

import AppLayout from '@/Components/templates/AppLayout.vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import { useTrendOptions } from './Partials/trendOptions';
const trendOptions = useTrendOptions();
import TrendSectionNav from './Partials/TrendSectionNav.vue';
import TrendTemplate from './Partials/TrendTemplate.vue';
import { useServerSearch } from '@/composables/useServerSearch';
import { formatMoney } from '@/utils';
import { formatMonth } from '@/utils';

interface Member {
    id: string;
    name: string;
    initial: string;
    accent: 'emerald' | 'blue' | 'slate';
    spend: number;
    topCategories: { name: string; amount: number }[];
}

interface CategoryRow {
    name: string;
    assigned: number;
    spent: number;
    splits: { memberId: string; amount: number }[];
}

const props = withDefaults(defineProps<{
    user?: Record<string, any>;
    data?: any;
    metaData?: Record<string, any>;
    serverSearchOptions?: Record<string, any>;
    section?: string;
}>(), {
    serverSearchOptions: () => ({}),
});

const { serverSearchOptions } = toRefs(props);
const { state: pageState, executeSearchWithDelay } = useServerSearch(serverSearchOptions, {
    manual: true,
});

const proportional = ref(false);
const activeFilter = ref<string>('all');

interface Accent {
    dot: string;
    chip: string;
    bar: string;
    avatar: string;
    pillActive: string;
    pillInactive: string;
}

const accents: Record<string, Accent> = {
    emerald: {
        dot: 'bg-emerald-500',
        chip: 'bg-emerald-500 text-white',
        bar: 'bg-emerald-500',
        avatar: 'bg-emerald-500 text-white',
        pillActive: 'bg-emerald-500 text-white',
        pillInactive: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100',
    },
    blue: {
        dot: 'bg-blue-500',
        chip: 'bg-blue-500 text-white',
        bar: 'bg-blue-500',
        avatar: 'bg-blue-500 text-white',
        pillActive: 'bg-blue-500 text-white',
        pillInactive: 'bg-blue-50 text-blue-700 hover:bg-blue-100',
    },
    slate: {
        dot: 'bg-slate-500',
        chip: 'bg-slate-500 text-white',
        bar: 'bg-slate-500',
        avatar: 'bg-slate-500 text-white',
        pillActive: 'bg-slate-500 text-white',
        pillInactive: 'bg-slate-50 text-slate-700 hover:bg-slate-100',
    },
};

const members = ref<Member[]>([
    {
        id: 'alice',
        name: 'Alice',
        initial: 'A',
        accent: 'emerald',
        spend: 612,
        topCategories: [
            { name: 'Comida', amount: 180 },
            { name: 'Transporte', amount: 120 },
            { name: 'Personal', amount: 312 },
        ],
    },
    {
        id: 'bob',
        name: 'Bob',
        initial: 'B',
        accent: 'blue',
        spend: 488,
        topCategories: [
            { name: 'Comida', amount: 145 },
            { name: 'Suscripciones', amount: 90 },
            { name: 'Personal', amount: 253 },
        ],
    },
    {
        id: 'household',
        name: 'Hogar',
        initial: 'H',
        accent: 'slate',
        spend: 230,
        topCategories: [
            { name: 'Renta', amount: 200 },
            { name: 'Servicios', amount: 30 },
        ],
    },
]);

const totalSpend = computed(() => members.value.reduce((sum, m) => sum + m.spend, 0));

const memberById = computed<Record<string, Member>>(() => {
    const map: Record<string, Member> = {};
    members.value.forEach(m => { map[m.id] = m; });
    return map;
});

const categoryRows = ref<CategoryRow[]>([
    {
        name: 'Comida',
        assigned: 400,
        spent: 325,
        splits: [
            { memberId: 'alice', amount: 180 },
            { memberId: 'bob', amount: 115 },
            { memberId: 'household', amount: 30 },
        ],
    },
    {
        name: 'Transporte',
        assigned: 200,
        spent: 145,
        splits: [
            { memberId: 'alice', amount: 100 },
            { memberId: 'bob', amount: 45 },
        ],
    },
    {
        name: 'Renta',
        assigned: 200,
        spent: 200,
        splits: [
            { memberId: 'household', amount: 200 },
        ],
    },
    {
        name: 'Suscripciones',
        assigned: 120,
        spent: 90,
        splits: [
            { memberId: 'bob', amount: 90 },
        ],
    },
    {
        name: 'Servicios',
        assigned: 50,
        spent: 30,
        splits: [
            { memberId: 'household', amount: 30 },
        ],
    },
]);

const sharePct = (memberSpend: number) => Math.round((memberSpend / totalSpend.value) * 100);
const splitPct = (row: CategoryRow, amount: number) => Math.round((amount / row.spent) * 100);
const usagePct = (row: CategoryRow) => Math.min(100, Math.round((row.spent / row.assigned) * 100));
</script>

<template>
    <AppLayout :title="metaData?.title ?? 'Relationships'">
        <template #header>
            <TrendSectionNav :sections="trendOptions">
                <template #actions>
                    <AtDatePager
                        class="w-full h-12 border-none bg-base-lvl-1 text-body"
                        v-model:startDate="pageState.dates.startDate"
                        v-model:endDate="pageState.dates.endDate"
                        @change="executeSearchWithDelay(500)"
                        controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                        next-mode="month">
            {{ formatMonth(pageState.dates.startDate, 'MMMM yyyy') }}
        </AtDatePager>
                </template>
            </TrendSectionNav>
        </template>

        <TrendTemplate title="Relationships" :hide-panel="true">
            <main class="py-3 space-y-4">
                <section class="px-5 py-4 bg-base-lvl-3 rounded-md">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <SectionTitle>Relaciones</SectionTitle>
                            <p class="text-sm text-body-1 mt-1">Cuánto aporta cada miembro al gasto del hogar</p>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-body-1 cursor-pointer select-none">
                            <input type="checkbox" v-model="proportional" class="rounded border-base" />
                            Vista proporcional
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-1.5 mt-4">
                        <button
                            @click="activeFilter = 'all'"
                            class="px-3.5 py-1.5 text-sm rounded-full transition-colors font-medium"
                            :class="activeFilter === 'all'
                                ? 'bg-body text-white'
                                : 'bg-base-lvl-2 text-body-1 hover:bg-base-lvl-1'"
                        >
                            Todos
                        </button>
                        <button
                            v-for="member in members"
                            :key="member.id"
                            @click="activeFilter = member.id"
                            class="px-3.5 py-1.5 text-sm rounded-full font-medium transition-colors"
                            :class="activeFilter === member.id
                                ? accents[member.accent].pillActive
                                : accents[member.accent].pillInactive"
                        >
                            {{ member.name }}
                        </button>
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <article
                        v-for="member in members"
                        :key="member.id"
                        class="bg-base-lvl-3 rounded-md border border-base p-5 hover:border-base-deep-1 transition-colors"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold"
                                    :class="accents[member.accent].avatar"
                                >
                                    {{ member.initial }}
                                </div>
                                <span class="text-sm font-semibold text-body">{{ member.name }}</span>
                            </div>
                            <span
                                class="text-xs font-bold px-2 py-0.5 rounded-full"
                                :class="accents[member.accent].chip"
                            >
                                {{ sharePct(member.spend) }}%
                            </span>
                        </div>

                        <div class="text-3xl font-bold text-body leading-none tabular-nums">
                            {{ formatMoney(member.spend) }}
                        </div>
                        <div class="text-xs text-body-1 mt-1.5 mb-4">
                            {{ member.id === 'household' ? 'Sin atribuir' : `de ${formatMoney(totalSpend)} total` }}
                        </div>

                        <div class="h-2 rounded-full bg-base-lvl-2 overflow-hidden mb-5">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="accents[member.accent].bar"
                                :style="{ width: sharePct(member.spend) + '%' }"
                            ></div>
                        </div>

                        <div class="space-y-1.5">
                            <p class="text-xs font-semibold text-body-1 uppercase tracking-wide mb-1">Top categorías</p>
                            <div
                                v-for="cat in member.topCategories"
                                :key="cat.name"
                                class="flex justify-between items-baseline text-sm"
                            >
                                <span class="text-body">{{ cat.name }}</span>
                                <span class="text-body-1 tabular-nums">{{ formatMoney(cat.amount) }}</span>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="bg-base-lvl-3 rounded-md border border-base overflow-hidden">
                    <div class="flex items-center justify-between flex-wrap gap-3 px-5 py-4 border-b border-base">
                        <div>
                            <SectionTitle>Por categoría</SectionTitle>
                            <p class="text-xs text-body-1 mt-0.5">Cuota de consumo por miembro</p>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-body-1">
                            <span
                                v-for="member in members"
                                :key="member.id"
                                class="inline-flex items-center gap-1.5"
                            >
                                <span class="inline-block w-2.5 h-2.5 rounded-sm" :class="accents[member.accent].dot"></span>
                                {{ member.name }}
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-base">
                        <div
                            v-for="row in categoryRows"
                            :key="row.name"
                            class="px-5 py-3.5 hover:bg-base-lvl-2/40 transition-colors"
                        >
                            <div class="flex items-baseline justify-between mb-2">
                                <span class="text-sm font-semibold text-body">{{ row.name }}</span>
                                <span class="text-xs text-body-1 tabular-nums">
                                    <span class="text-body font-semibold">{{ formatMoney(row.spent) }}</span>
                                    <span class="mx-1">·</span>
                                    {{ usagePct(row) }}% de {{ formatMoney(row.assigned) }}
                                </span>
                            </div>
                            <div class="flex h-3 rounded-full overflow-hidden bg-base-lvl-2">
                                <div
                                    v-for="split in row.splits"
                                    :key="split.memberId"
                                    class="h-full flex-shrink-0 transition-all"
                                    :class="accents[memberById[split.memberId]?.accent || 'slate'].bar"
                                    :style="{ width: splitPct(row, split.amount) + '%' }"
                                    :title="`${memberById[split.memberId]?.name}: ${formatMoney(split.amount)}`"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-base-lvl-2/50 border-t border-base">
                        <p class="text-xs text-body-1 leading-relaxed">
                            Las cuotas son el monto de cada transacción dividido entre los miembros atribuidos. Las transacciones sin atribuir cuentan como Hogar — eso es el default honesto, no un tag faltante.
                        </p>
                    </div>
                </section>

                <p class="text-xs text-body-1 italic text-center px-2">
                    Mock — backend pendiente. Ver <code class="bg-base-lvl-2 px-1.5 py-0.5 rounded">.planning/features/couple-support.md</code>.
                </p>
            </main>
        </TrendTemplate>
    </AppLayout>
</template>
