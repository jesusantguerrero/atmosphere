<script setup lang="ts">
import { ref, computed, toRefs } from 'vue';
// @ts-ignore
import { AtDatePager } from 'atmosphere-ui';
import AppLayout from '@/Components/templates/AppLayout.vue';
import { trendOptions } from './Partials/trendOptions';
import TrendSectionNav from './Partials/TrendSectionNav.vue';
import TrendTemplate from './Partials/TrendTemplate.vue';
import { useServerSearch } from '@/composables/useServerSearch';

interface Member {
    id: string;
    name: string;
    initial: string;
    color: string;
    bgColor: string;
    accent: string;
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

const fmt = (value: number) => {
    const code = (window as any)?.logerAppSettings?.currency_code ?? 'USD';
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: code,
            maximumFractionDigits: 0,
            minimumFractionDigits: 0,
        }).format(Number(value) || 0);
    } catch {
        return `$${value}`;
    }
};

const proportional = ref(false);
const activeFilter = ref<string>('all');

const members = ref<Member[]>([
    {
        id: 'alice',
        name: 'Alice',
        initial: 'A',
        color: '#3C3489',
        bgColor: '#EEEDFE',
        accent: '#7F77DD',
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
        color: '#085041',
        bgColor: '#E1F5EE',
        accent: '#1D9E75',
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
        color: '#444441',
        bgColor: '#F1EFE8',
        accent: '#888780',
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
    <AppLayout :title="metaData?.title ?? 'Relaciones'">
        <template #header>
            <TrendSectionNav :sections="trendOptions">
                <template #actions>
                    <AtDatePager
                        class="w-full h-12 border-none bg-base-lvl-1 text-body"
                        v-model:startDate="pageState.dates.startDate"
                        v-model:endDate="pageState.dates.endDate"
                        @change="executeSearchWithDelay(500)"
                        controlsClass="bg-transparent text-body hover:bg-base-lvl-1"
                        next-mode="month"
                    />
                </template>
            </TrendSectionNav>
        </template>

        <TrendTemplate title="Relaciones" :hide-panel="true">
            <div class="space-y-6 pb-8">

                <header class="flex items-end justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-body-1 leading-tight">Relaciones</h1>
                        <p class="text-sm text-body-3 mt-1">Cuánto aporta cada miembro al gasto del hogar</p>
                    </div>
                </header>

                <section class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex gap-1.5">
                        <button
                            @click="activeFilter = 'all'"
                            :class="[
                                'px-3.5 py-1.5 text-sm rounded-full transition-colors',
                                activeFilter === 'all'
                                    ? 'bg-body-1 text-white font-medium'
                                    : 'bg-base-lvl-1 border border-base-lvl-2 text-body-3 hover:text-body-1'
                            ]"
                        >
                            Todos
                        </button>
                        <button
                            v-for="member in members"
                            :key="member.id"
                            @click="activeFilter = member.id"
                            class="px-3.5 py-1.5 text-sm rounded-full font-medium transition-opacity"
                            :class="activeFilter !== member.id && activeFilter !== 'all' ? 'opacity-50 hover:opacity-100' : ''"
                            :style="{ background: member.bgColor, color: member.color }"
                        >
                            {{ member.name }}
                        </button>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-body-3 cursor-pointer select-none">
                        <input type="checkbox" v-model="proportional" class="rounded border-base-lvl-2" />
                        Vista proporcional
                    </label>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <article
                        v-for="member in members"
                        :key="member.id"
                        class="bg-base-lvl-1 rounded-xl p-5 border border-base-lvl-2 hover:border-base-lvl-3 transition-colors"
                    >
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                                    :style="{ background: member.bgColor, color: member.color }"
                                >
                                    {{ member.initial }}
                                </div>
                                <span class="text-sm font-medium text-body-1">{{ member.name }}</span>
                            </div>
                            <span
                                class="text-xs font-medium px-2 py-0.5 rounded-full"
                                :style="{ background: member.bgColor, color: member.color }"
                            >
                                {{ sharePct(member.spend) }}%
                            </span>
                        </div>

                        <div class="text-3xl font-bold text-body-1 leading-none tabular-nums">
                            {{ fmt(member.spend) }}
                        </div>
                        <div class="text-xs text-body-3 mt-1.5 mb-4">
                            {{ member.id === 'household' ? 'Sin atribuir' : `de ${fmt(totalSpend)} total` }}
                        </div>

                        <div class="h-2 rounded-full bg-base-lvl-3 overflow-hidden mb-5">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="{ width: sharePct(member.spend) + '%', background: member.accent }"
                            ></div>
                        </div>

                        <div class="space-y-1.5">
                            <p class="text-xs font-medium text-body-3 uppercase tracking-wide mb-1">Top categorías</p>
                            <div
                                v-for="cat in member.topCategories"
                                :key="cat.name"
                                class="flex justify-between items-baseline text-sm"
                            >
                                <span class="text-body-1">{{ cat.name }}</span>
                                <span class="text-body-3 tabular-nums">{{ fmt(cat.amount) }}</span>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="bg-base-lvl-1 rounded-xl border border-base-lvl-2 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-base-lvl-2">
                        <div>
                            <h2 class="text-base font-semibold text-body-1">Por categoría</h2>
                            <p class="text-xs text-body-3 mt-0.5">Cuota de consumo por miembro</p>
                        </div>
                        <div class="flex gap-4 text-xs text-body-3">
                            <span
                                v-for="member in members"
                                :key="member.id"
                                class="inline-flex items-center gap-1.5"
                            >
                                <span class="inline-block w-2.5 h-2.5 rounded-sm" :style="{ background: member.accent }"></span>
                                {{ member.name }}
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-base-lvl-2">
                        <div
                            v-for="row in categoryRows"
                            :key="row.name"
                            class="px-5 py-3.5 hover:bg-base-lvl-3/40 transition-colors"
                        >
                            <div class="flex items-baseline justify-between mb-2">
                                <span class="text-sm font-medium text-body-1">{{ row.name }}</span>
                                <span class="text-xs text-body-3 tabular-nums">
                                    <span class="text-body-1 font-medium">{{ fmt(row.spent) }}</span>
                                    <span class="mx-1">·</span>
                                    {{ usagePct(row) }}% de {{ fmt(row.assigned) }}
                                </span>
                            </div>
                            <div class="flex h-3 rounded-full overflow-hidden bg-base-lvl-3">
                                <div
                                    v-for="split in row.splits"
                                    :key="split.memberId"
                                    class="h-full flex-shrink-0 transition-all"
                                    :style="{
                                        width: splitPct(row, split.amount) + '%',
                                        background: memberById[split.memberId]?.accent || '#888780',
                                    }"
                                    :title="`${memberById[split.memberId]?.name}: ${fmt(split.amount)}`"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-base-lvl-3/50 border-t border-base-lvl-2">
                        <p class="text-xs text-body-3 leading-relaxed">
                            Las cuotas son el monto de cada transacción dividido entre los miembros atribuidos.
                            Las transacciones sin atribuir cuentan como Hogar — eso es el default honesto, no un tag faltante.
                        </p>
                    </div>
                </section>

                <p class="text-xs text-body-3 italic text-center">
                    Mock — ver <code class="bg-base-lvl-1 px-1.5 py-0.5 rounded text-body-3">.planning/features/couple-support.md</code> para el plan del backend
                </p>

            </div>
        </TrendTemplate>
    </AppLayout>
</template>
