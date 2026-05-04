<script setup lang="ts">
import { computed, ref } from 'vue';

import AppLayout from '@/Components/templates/AppLayout.vue';
import FinanceSectionNav from '@/Pages/Finance/Partials/FinanceSectionNav.vue';
import FinanceTemplate from '@/Pages/Finance/Partials/FinanceTemplate.vue';
import SectionTitle from '@/Components/atoms/SectionTitle.vue';
import InputMoney from '@/Components/atoms/InputMoney.vue';
import { formatMoney } from '@/utils';

interface Plan {
    id: 'A' | 'B' | 'C';
    name: string;
    description: string;
    accent: 'emerald' | 'blue' | 'amber';
    inicialPct: number;
    plazoYears: number;
    rate: number;
}

interface ComputedPlan extends Plan {
    inicial: number;
    financiamiento: number;
    cuota: number;
    totalPagado: number;
    intereses: number;
    cierre: number;
    bufferAnual: number;
    savingGoal: number;
    dtiPct: number;
    pct: number;
    remaining: number;
    monthsToGoal: number | null;
}

const numberInputClass =
    'w-full px-3 py-2 text-sm border border-base rounded bg-base-lvl-2 text-body focus:outline-none focus:ring-1 focus:ring-primary';
const cellInputClass =
    'w-20 px-2 py-1 text-center text-sm border border-base rounded bg-base-lvl-2 text-body font-medium focus:outline-none focus:ring-1 focus:ring-primary';

const valor = ref(3300000);
const cuotaFavorable = ref(25000);
const ahorros = ref(1674104);
const bono = ref(0);
const ingresoMensual = ref(150000);
const ahorroMensual = ref(35000);
const cierrePct = ref(4.5);
const includeBuffer = ref(true);

const plans = ref<Plan[]>([
    {
        id: 'A',
        name: 'Conservador',
        description: 'Más inicial, menos años. Pagas menos intereses pero ahorras más antes de comprar.',
        accent: 'emerald',
        inicialPct: 30,
        plazoYears: 15,
        rate: 9.5,
    },
    {
        id: 'B',
        name: 'Balanceado',
        description: 'Punto medio entre inicial razonable y cuota manejable.',
        accent: 'blue',
        inicialPct: 20,
        plazoYears: 20,
        rate: 10.5,
    },
    {
        id: 'C',
        name: 'Estirado',
        description: 'Mínimo inicial, plazo máximo. Entras rápido pero pagas mucho interés.',
        accent: 'amber',
        inicialPct: 10,
        plazoYears: 25,
        rate: 11.5,
    },
]);

const selectedPlanId = ref<'A' | 'B' | 'C'>('B');

const fondosDisponibles = computed(() => Number(ahorros.value) + Number(bono.value));
const cierreAmount = computed(() => valor.value * (cierrePct.value / 100));

function pmt(principal: number, annualRate: number, years: number): number {
    if (principal <= 0) {
        return 0;
    }
    const r = annualRate / 100 / 12;
    const n = years * 12;
    if (r === 0) {
        return principal / n;
    }
    return (principal * (r * Math.pow(1 + r, n))) / (Math.pow(1 + r, n) - 1);
}

function buildComputed(plan: Plan): ComputedPlan {
    const inicial = Math.min(Math.max(valor.value * (plan.inicialPct / 100), 0), valor.value);
    const financiamiento = Math.max(valor.value - inicial, 0);
    const cuota = pmt(financiamiento, plan.rate, plan.plazoYears);
    const totalPagado = cuota * plan.plazoYears * 12 + inicial;
    const intereses = Math.max(totalPagado - valor.value, 0);
    const bufferAnual = includeBuffer.value ? cuota * 12 : 0;
    const savingGoal = inicial + cierreAmount.value + bufferAnual;
    const dtiPct = ingresoMensual.value > 0 ? (cuota / ingresoMensual.value) * 100 : 0;
    const pct = cuotaFavorable.value > 0 ? cuota / cuotaFavorable.value : 0;
    const remaining = Math.max(savingGoal - fondosDisponibles.value, 0);
    const monthsToGoal = ahorroMensual.value > 0
        ? remaining > 0
            ? Math.ceil(remaining / ahorroMensual.value)
            : 0
        : null;

    return {
        ...plan,
        inicial,
        financiamiento,
        cuota,
        totalPagado,
        intereses,
        cierre: cierreAmount.value,
        bufferAnual,
        savingGoal,
        dtiPct,
        pct,
        remaining,
        monthsToGoal,
    };
}

const computedPlans = computed<ComputedPlan[]>(() => plans.value.map(buildComputed));
const selectedPlan = computed(() => computedPlans.value.find((p) => p.id === selectedPlanId.value)!);
const selectedRaw = computed(() => plans.value.find((p) => p.id === selectedPlanId.value)!);

function pctClass(pct: number): string {
    if (pct <= 1) {
        return 'text-emerald-600';
    }
    if (pct <= 1.1) {
        return 'text-amber-600';
    }
    return 'text-red-600';
}

function dtiStatus(dti: number): { label: string; tone: string } {
    if (dti <= 30) {
        return { label: 'Cómodo', tone: 'text-emerald-600' };
    }
    if (dti <= 35) {
        return { label: 'Apretado', tone: 'text-amber-600' };
    }
    return { label: 'No califica', tone: 'text-red-600' };
}

function dtiChipClass(dti: number): string {
    if (dti <= 30) {
        return 'bg-emerald-500 text-white';
    }
    if (dti <= 35) {
        return 'bg-amber-500 text-white';
    }
    return 'bg-red-500 text-white';
}

interface Accent {
    dot: string;
    rowSelected: string;
    cardBg: string;
    cardBorder: string;
    cardRing: string;
    title: string;
    accentText: string;
    headerStrip: string;
    iconCircle: string;
    bigStatBg: string;
    chip: string;
    statRing: string;
}

const accentClasses: Record<string, Accent> = {
    emerald: {
        dot: 'bg-emerald-500',
        rowSelected: 'bg-emerald-50',
        cardBg: 'bg-gradient-to-br from-emerald-50 to-emerald-100/40',
        cardBorder: 'border-emerald-300',
        cardRing: 'shadow-[0_4px_24px_-8px_rgba(16,185,129,0.25)]',
        title: 'text-emerald-700',
        accentText: 'text-emerald-700',
        headerStrip: 'bg-emerald-500',
        iconCircle: 'bg-emerald-500 text-white',
        bigStatBg: 'bg-gradient-to-r from-emerald-100 to-emerald-50 ring-1 ring-emerald-200',
        chip: 'bg-emerald-500 text-white',
        statRing: 'ring-1 ring-emerald-200/60',
    },
    blue: {
        dot: 'bg-blue-500',
        rowSelected: 'bg-blue-50',
        cardBg: 'bg-gradient-to-br from-blue-50 to-indigo-100/40',
        cardBorder: 'border-blue-300',
        cardRing: 'shadow-[0_4px_24px_-8px_rgba(59,130,246,0.25)]',
        title: 'text-blue-700',
        accentText: 'text-blue-700',
        headerStrip: 'bg-blue-500',
        iconCircle: 'bg-blue-500 text-white',
        bigStatBg: 'bg-gradient-to-r from-blue-100 to-blue-50 ring-1 ring-blue-200',
        chip: 'bg-blue-500 text-white',
        statRing: 'ring-1 ring-blue-200/60',
    },
    amber: {
        dot: 'bg-amber-500',
        rowSelected: 'bg-amber-50',
        cardBg: 'bg-gradient-to-br from-amber-50 to-orange-100/40',
        cardBorder: 'border-amber-300',
        cardRing: 'shadow-[0_4px_24px_-8px_rgba(245,158,11,0.25)]',
        title: 'text-amber-700',
        accentText: 'text-amber-700',
        headerStrip: 'bg-amber-500',
        iconCircle: 'bg-amber-500 text-white',
        bigStatBg: 'bg-gradient-to-r from-amber-100 to-amber-50 ring-1 ring-amber-200',
        chip: 'bg-amber-500 text-white',
        statRing: 'ring-1 ring-amber-200/60',
    },
};

function monthsLabel(months: number | null): string {
    if (months === null) {
        return '—';
    }
    if (months === 0) {
        return 'Ya alcanzado';
    }
    if (months < 12) {
        return `${months}m`;
    }
    const years = (months / 12).toFixed(1);
    return `${months}m (${years}a)`;
}

function fmtNoCents(value: number): string {
    const symbol = (window as any)?.logerAppSettings?.currency_code ?? 'DOP';
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: symbol,
            currencyDisplay: 'symbol',
            maximumFractionDigits: 0,
        }).format(Number(value) || 0);
    } catch {
        return String(value);
    }
}

function fmtCompact(value: number): string {
    const symbol = (window as any)?.logerAppSettings?.currency_code ?? 'DOP';
    try {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: symbol,
            currencyDisplay: 'symbol',
            notation: 'compact',
            maximumFractionDigits: 2,
        }).format(Number(value) || 0);
    } catch {
        return String(value);
    }
}
</script>

<template>
    <AppLayout title="House Buyer Planner">
        <template #header>
            <FinanceSectionNav />
        </template>

        <FinanceTemplate panel-size="large" force-show-panel>
            <main class="py-3 space-y-4">
                <section class="px-5 py-4 bg-base-lvl-3 rounded-md">
                    <SectionTitle>Tu situación</SectionTitle>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3">
                        <label class="block">
                            <span class="text-xs text-body-1">Valor del inmueble</span>
                            <InputMoney v-model="valor" :number-format="true" class="mt-1">
                                <template #prefix><span class="flex items-center pl-2">RD$</span></template>
                            </InputMoney>
                        </label>
                        <label class="block">
                            <span class="text-xs text-body-1">Cuota deseada</span>
                            <InputMoney v-model="cuotaFavorable" :number-format="true" class="mt-1">
                                <template #prefix><span class="flex items-center pl-2">RD$</span></template>
                            </InputMoney>
                        </label>
                        <label class="block">
                            <span class="text-xs text-body-1">Ahorros actuales</span>
                            <InputMoney v-model="ahorros" :number-format="true" class="mt-1">
                                <template #prefix><span class="flex items-center pl-2">RD$</span></template>
                            </InputMoney>
                        </label>
                        <label class="block">
                            <span class="text-xs text-body-1">Bono extra</span>
                            <InputMoney v-model="bono" :number-format="true" class="mt-1">
                                <template #prefix><span class="flex items-center pl-2">RD$</span></template>
                            </InputMoney>
                        </label>
                        <label class="block">
                            <span class="text-xs text-body-1">Ingreso mensual neto</span>
                            <InputMoney v-model="ingresoMensual" :number-format="true" class="mt-1">
                                <template #prefix><span class="flex items-center pl-2">RD$</span></template>
                            </InputMoney>
                        </label>
                        <label class="block">
                            <span class="text-xs text-body-1">Ahorro mensual</span>
                            <InputMoney v-model="ahorroMensual" :number-format="true" class="mt-1">
                                <template #prefix><span class="flex items-center pl-2">RD$</span></template>
                            </InputMoney>
                        </label>
                        <label class="block">
                            <span class="text-xs text-body-1">% Cierre (ITBI + legales)</span>
                            <input v-model.number="cierrePct" type="number" step="0.1" :class="numberInputClass" />
                        </label>
                        <label class="flex items-center gap-2 self-end pb-2 cursor-pointer">
                            <input v-model="includeBuffer" type="checkbox" class="rounded" />
                            <span class="text-xs text-body">Incluir 1 año de cuotas en saving goal</span>
                        </label>
                    </div>
                </section>

                <section class="px-5 py-4 bg-base-lvl-3 rounded-md">
                    <SectionTitle>Comparación de planes</SectionTitle>
                    <p class="text-xs text-body-1 mb-3">Click una fila para ver el detalle al lado. Edita inicial/plazo/tasa inline.</p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="text-body-1 text-xs uppercase">
                                <tr class="border-b border-base">
                                    <th class="px-3 py-2 text-left font-medium">Plan</th>
                                    <th class="px-2 py-2 text-center font-medium">Inicial %</th>
                                    <th class="px-2 py-2 text-center font-medium">Plazo</th>
                                    <th class="px-2 py-2 text-center font-medium">Tasa %</th>
                                    <th class="px-3 py-2 text-right font-medium border-l border-base">Cuota</th>
                                    <th class="px-3 py-2 text-right font-medium">PCT</th>
                                    <th class="px-3 py-2 text-right font-medium">Saving Goal</th>
                                    <th class="px-3 py-2 text-right font-medium">Intereses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(p, idx) in computedPlans"
                                    :key="p.id"
                                    class="border-b border-base/40 cursor-pointer transition"
                                    :class="selectedPlanId === p.id ? accentClasses[p.accent].rowSelected : 'hover:bg-base-lvl-2/40'"
                                    @click="selectedPlanId = p.id"
                                >
                                    <td class="px-3 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full" :class="accentClasses[p.accent].dot" />
                                            <span class="font-semibold text-body">Plan {{ p.id }}</span>
                                            <span class="text-xs text-body-1">{{ p.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 text-center" @click.stop>
                                        <input
                                            v-model.number="plans[idx].inicialPct"
                                            type="number"
                                            step="1"
                                            min="0"
                                            max="100"
                                            :class="cellInputClass"
                                        />
                                    </td>
                                    <td class="px-2 py-2 text-center" @click.stop>
                                        <input
                                            v-model.number="plans[idx].plazoYears"
                                            type="number"
                                            step="1"
                                            min="1"
                                            :class="cellInputClass"
                                        />
                                    </td>
                                    <td class="px-2 py-2 text-center" @click.stop>
                                        <input
                                            v-model.number="plans[idx].rate"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            :class="cellInputClass"
                                        />
                                    </td>
                                    <td class="px-3 py-3 text-right font-semibold border-l border-base">{{ formatMoney(p.cuota) }}</td>
                                    <td class="px-3 py-3 text-right font-semibold" :class="pctClass(p.pct)">
                                        {{ Math.round(p.pct * 100) }}%
                                    </td>
                                    <td class="px-3 py-3 text-right">{{ formatMoney(p.savingGoal) }}</td>
                                    <td class="px-3 py-3 text-right font-semibold text-red-600">
                                        {{ formatMoney(p.intereses) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <p class="text-xs text-body-1 px-2">
                    Las tasas y % de gastos de cierre son referenciales del mercado RD. Cada banco puede variar. Esto es una herramienta de planificación, no asesoría financiera formal.
                </p>
            </main>

            <template #prepend-panel>
                <div class="bg-base-lvl-3 rounded-md p-3 mt-4">
                <div
                    v-if="selectedPlan"
                    class="rounded-xl border overflow-hidden"
                    :class="[
                        accentClasses[selectedPlan.accent].cardBg,
                        accentClasses[selectedPlan.accent].cardBorder,
                        accentClasses[selectedPlan.accent].cardRing,
                    ]"
                >
                    <div class="h-1.5" :class="accentClasses[selectedPlan.accent].headerStrip" />

                    <div class="p-4 space-y-4">
                        <div class="flex items-start gap-3">
                            <span
                                class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-base shrink-0"
                                :class="accentClasses[selectedPlan.accent].iconCircle"
                            >
                                {{ selectedPlan.id }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold leading-tight" :class="accentClasses[selectedPlan.accent].title">
                                    Plan {{ selectedPlan.id }} · {{ selectedPlan.name }}
                                </h3>
                                <div class="text-xs text-body-1">
                                    {{ selectedPlan.rate.toFixed(2) }}% · {{ selectedPlan.plazoYears }} años
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-body-1 leading-snug">{{ selectedPlan.description }}</p>

                        <div class="rounded-lg p-3" :class="accentClasses[selectedPlan.accent].bigStatBg">
                            <div class="text-xs uppercase tracking-wide font-bold" :class="accentClasses[selectedPlan.accent].accentText">
                                Cuota mensual
                            </div>
                            <div class="text-2xl font-extrabold text-body leading-tight mt-1 truncate">
                                {{ fmtNoCents(selectedPlan.cuota) }}
                            </div>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold" :class="accentClasses[selectedPlan.accent].chip">
                                    {{ Math.round(selectedPlan.pct * 100) }}% de tu deseada
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold inline-flex items-center gap-1" :class="dtiChipClass(selectedPlan.dtiPct)">
                                    {{ selectedPlan.dtiPct.toFixed(0) }}% · {{ dtiStatus(selectedPlan.dtiPct).label }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-md p-2 bg-white min-w-0" :class="accentClasses[selectedPlan.accent].statRing">
                                <div class="text-xs text-body-1 truncate">Inicial ({{ selectedRaw.inicialPct }}%)</div>
                                <div class="font-bold text-body text-sm truncate">{{ fmtNoCents(selectedPlan.inicial) }}</div>
                            </div>
                            <div class="rounded-md p-2 bg-white min-w-0" :class="accentClasses[selectedPlan.accent].statRing">
                                <div class="text-xs text-body-1 truncate">Cierre (~{{ cierrePct }}%)</div>
                                <div class="font-bold text-body text-sm truncate">{{ fmtNoCents(selectedPlan.cierre) }}</div>
                            </div>
                            <div class="rounded-md p-2 bg-white min-w-0" :class="accentClasses[selectedPlan.accent].statRing">
                                <div class="text-xs text-body-1 truncate">A financiar</div>
                                <div class="font-bold text-body text-sm truncate">{{ fmtNoCents(selectedPlan.financiamiento) }}</div>
                            </div>
                            <div class="rounded-md p-2 bg-white min-w-0" :class="accentClasses[selectedPlan.accent].statRing">
                                <div class="text-xs text-body-1 truncate">1 año en cuota</div>
                                <div class="font-bold text-body text-sm truncate">{{ fmtNoCents(selectedPlan.cuota * 12) }}</div>
                            </div>
                        </div>

                        <div class="rounded-lg p-3 bg-white" :class="accentClasses[selectedPlan.accent].statRing">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" :class="accentClasses[selectedPlan.accent].iconCircle">
                                    <i class="fa fa-bullseye text-xs"></i>
                                </span>
                                <span class="font-bold text-body text-sm">Saving Goal</span>
                            </div>
                            <div class="text-2xl font-extrabold leading-tight truncate" :class="accentClasses[selectedPlan.accent].title">
                                {{ fmtNoCents(selectedPlan.savingGoal) }}
                            </div>
                            <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-base/40">
                                <div class="min-w-0">
                                    <div class="text-xs text-body-1 uppercase tracking-wide">Te falta</div>
                                    <div class="text-sm font-bold text-body truncate">{{ fmtNoCents(selectedPlan.remaining) }}</div>
                                </div>
                                <div class="min-w-0 text-right">
                                    <div class="text-xs text-body-1 uppercase tracking-wide">A tu ritmo</div>
                                    <div class="text-sm font-bold text-body truncate">{{ monthsLabel(selectedPlan.monthsToGoal) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-md p-2 bg-white min-w-0" :class="accentClasses[selectedPlan.accent].statRing">
                                <div class="text-xs text-body-1 truncate">Total pagado</div>
                                <div class="font-bold text-body text-sm truncate">{{ fmtCompact(selectedPlan.totalPagado) }}</div>
                            </div>
                            <div class="rounded-md p-2 bg-red-50 ring-1 ring-red-200 min-w-0">
                                <div class="text-xs text-red-600 font-semibold uppercase tracking-wide truncate">Intereses</div>
                                <div class="font-bold text-red-600 text-sm truncate">{{ fmtCompact(selectedPlan.intereses) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </template>
        </FinanceTemplate>
    </AppLayout>
</template>
