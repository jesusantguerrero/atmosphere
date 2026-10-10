<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AccountModal from '@/domains/transactions/components/AccountModal.vue';

interface JourneyCard {
  id: number; name: string; currency: string; spent: number; points: number | null;
  estimated_value: number | null; multi_currency: boolean; opened_at: string | null;
  closed_at: string | null; active_in_period: boolean; closed_before_period: boolean; rules: { points?: number; spend?: number };
}
interface SnapshotCard { id: number; name: string; currency: string; balance: number | null; opening_known: boolean }
interface JourneyEvent {
  id: string; account_id?: number; date: string; kind: string; name: string;
  date_precision?: 'day' | 'month'; snapshot_date?: string;
  cards: SnapshotCard[]; before_cards?: SnapshotCard[];
}
const props = defineProps<{
  journey: { events: JourneyEvent[]; cards: JourneyCard[]; missing_opening_dates: number };
  billing: Array<{ id: number; discounts: number }>;
}>();
const selectedId = ref('');
const editingAccount = ref<any>(null);
const page = usePage();
const missingDates = computed(() => props.journey.cards.filter(card => !card.opened_at));
const accountFor = (id: number) => (page.props.accounts as any[] || []).find(account => Number(account.id) === id);
const includeClosed = ref(false);
const selected = computed(() => props.journey.events.find(event => event.id === selectedId.value) || props.journey.events.at(-1));
const visibleCards = computed(() => props.journey.cards.filter(card => card.active_in_period || (includeClosed.value && card.closed_before_period)));
const dateLabel = (date: string, precision = 'day') => new Intl.DateTimeFormat('es', { ...(precision === 'month' ? {} : { day: 'numeric' as const }), month: 'short', year: 'numeric' }).format(new Date(`${date}T12:00:00`));
const money = (value: number, currency: string) => new Intl.NumberFormat('es-DO', { style: 'currency', currency, currencyDisplay: 'code' }).format(value);
const eventLabel = (event: JourneyEvent, index: number) => event.kind === 'today' ? 'Así estás hoy'
  : event.kind === 'period' ? 'Foto del período'
  : event.kind === 'closed' ? `Cerraste ${event.name}`
  : index === 0 ? 'Tu primera apertura registrada' : `Llegó ${event.name}`;
const eventTitle = computed(() => selected.value?.kind === 'opened' ? 'Una nueva tarjeta en tu historia.'
  : selected.value?.kind === 'closed' ? 'Una etapa que queda en tu historia.'
  : selected.value?.kind === 'period' ? 'Así estabas en este período.' : 'Esta es tu foto más reciente.');
const eventStory = computed(() => {
  const event = selected.value;
  if (!event) return 'Agrega las fechas reales de apertura para empezar a recorrer tu historia.';
  if (event.kind === 'opened') return `${event.name} se sumó a tus tarjetas. Mira cómo estabas en ese momento y compara tu situación antes y después de su llegada.`;
  if (event.kind === 'closed') return `Cerraste ${event.name}. Sus movimientos anteriores siguen en tu historia; puedes volver a ellos y ver cómo cambió tu relación con las tarjetas.`;
  return 'Estas son las tarjetas que podemos identificar en esta fecha y su deuda registrada. Elige un hito para viajar a otro momento.';
});
const debtSummary = (cards: SnapshotCard[]) => {
  const groups: Record<string, number> = {};
  cards.filter(card => card.balance !== null).forEach(card => { groups[card.currency] = (groups[card.currency] || 0) + Number(card.balance); });
  return Object.entries(groups).map(([currency, total]) => money(total, currency)).join(' / ') || 'Sin datos';
};
const cardCount = (cards: SnapshotCard[]) => !cards.length && props.journey.missing_opening_dates ? 'Por completar' : String(cards.length);
const discounts = (id: number) => Number(props.billing.find(card => Number(card.id) === id)?.discounts || 0);
watch(() => props.journey, () => { selectedId.value = ''; }, { deep: true });
</script>

<template>
  <section class="history-panel mt-4 bg-base-lvl-3 text-body" aria-labelledby="card-history-title">
    <header class="history-heading">
      <div class="history-heading-copy">
        <span class="history-icon text-primary" aria-hidden="true">↶</span>
        <div>
          <h2 id="card-history-title">Mi historia con las tarjetas</h2>
          <p class="text-body-1/70">Elige un momento y mira cómo estabas.</p>
        </div>
      </div>
      <span class="history-date-badge">Fechas reales</span>
    </header>

    <div v-if="journey.events.length" class="history-timeline" role="group" aria-label="Hitos de tus tarjetas">
      <button v-for="(event, index) in journey.events" :key="event.id" type="button"
        class="history-event" :class="{ 'is-selected': selected?.id === event.id }"
        :aria-pressed="selected?.id === event.id" @click="selectedId = event.id">
        <span class="history-dot" aria-hidden="true"></span>
        <strong>{{ eventLabel(event, index) }}</strong>
        <span>{{ dateLabel(event.date, event.date_precision) }}</span>
      </button>
    </div>

    <div class="history-snapshot bg-base-lvl-2" aria-live="polite">
      <div class="history-narrative">
        <span v-if="selected" class="history-kicker text-primary">
          {{ dateLabel(selected.date, selected.date_precision) }} · {{ selected.kind === 'opened' ? 'Apertura' : selected.kind === 'closed' ? 'Cierre' : 'Tu situación' }}
        </span>
        <h3>{{ selected ? eventTitle : 'Tu historia está por completar.' }}</h3>
        <p class="text-body-1/70">{{ eventStory }}</p>
        <div v-if="selected" class="history-metrics">
          <div>
            <strong>{{ cardCount(selected.cards) }}</strong>
            <span class="text-body-1/70">{{ journey.missing_opening_dates && selected.kind !== 'today' ? 'aperturas conocidas activas' : 'tarjetas activas' }}</span>
          </div>
          <div>
            <strong>{{ debtSummary(selected.cards) }}</strong>
            <span class="text-body-1/70">deuda registrada</span>
          </div>
        </div>
      </div>
      <div class="history-card-list">
        <template v-if="selected?.cards.length">
          <div v-for="card in selected.cards" :key="card.id" class="history-card-row">
            <div>
              <b>{{ card.name }}</b>
              <span v-if="selected.kind === 'opened' && selected.account_id === card.id" class="text-primary"> · Nueva</span>
              <span v-else-if="!card.opening_known" class="history-card-note text-body-1/60">Apertura por completar</span>
            </div>
            <span class="text-body-1/70">{{ card.balance === null ? 'Sin saldo histórico' : money(card.balance, card.currency) }}</span>
          </div>
          <p class="history-footnote text-body-1/60">Saldos verificados en la moneda principal a esta fecha. Los datos que faltan no se muestran como cero. El límite histórico no está registrado.</p>
        </template>
        <div v-else class="history-incomplete">
          <span class="text-primary history-incomplete-icon" aria-hidden="true">↶</span>
          <h4>{{ journey.missing_opening_dates ? 'Completemos este momento' : 'Sin tarjetas activas en esta fecha' }}</h4>
          <p class="text-body-1/70">{{ journey.missing_opening_dates ? 'Faltan las fechas de apertura para saber qué tarjetas tenías entonces. Añádelas abajo y esta foto empezará a contar tu historia.' : 'Elige otro momento en la línea de tiempo para ver las tarjetas que tenías entonces.' }}</p>
        </div>
      </div>
    </div>

    <p v-if="selected?.date_precision === 'month'" class="history-footnote text-body-1/60">Apertura conocida por mes; el día exacto no está registrado. Esta foto corresponde al {{ dateLabel(selected.snapshot_date || selected.date) }}.</p>
    <details v-if="selected && ['opened', 'closed'].includes(selected.kind)" :key="selected.id" class="history-comparison border-base">
      <summary class="text-primary">Comparar antes y después de este momento</summary>
      <div class="history-comparison-grid">
        <article class="bg-base-lvl-2">
          <span class="text-body-1/60">Antes · {{ selected.date_precision === 'month' ? 'fin del mes anterior' : 'día anterior' }}</span>
          <strong>{{ cardCount(selected.before_cards || []) }} tarjetas</strong>
          <p>{{ debtSummary(selected.before_cards || []) }}</p>
        </article>
        <article class="bg-base-lvl-2">
          <span class="text-body-1/60">Después · {{ dateLabel(selected.snapshot_date || selected.date) }}</span>
          <strong>{{ cardCount(selected.cards) }} tarjetas</strong>
          <p>{{ debtSummary(selected.cards) }}</p>
        </article>
      </div>
      <p class="history-footnote text-body-1/60">Comparación con aperturas conocidas y movimientos verificados hasta cada día. Las monedas se mantienen separadas.</p>
    </details>

    <details v-if="missingDates.length" class="history-completion border-base">
      <summary class="text-body-1/70">{{ missingDates.length }} fechas de apertura por completar</summary>
      <p class="history-footnote text-body-1/60">Usa la fecha en que el banco te otorgó cada tarjeta. La fecha de creación en Loger no sustituye la apertura real.</p>
      <div class="history-missing-list">
        <div v-for="card in missingDates" :key="card.id" class="history-missing-row">
          <span>{{ card.name }}</span>
          <button v-if="accountFor(card.id)" type="button" class="text-primary" @click="editingAccount = accountFor(card.id)">Añadir fecha</button>
          <Link v-else :href="route('finance.accounts.show', card.id)" class="text-primary">Editar tarjeta</Link>
        </div>
      </div>
    </details>
    <AccountModal v-if="editingAccount" :show="true" :form-data="editingAccount" @close="editingAccount = null" />
  </section>

  <section class="mt-4 rounded-lg border border-base bg-base-lvl-3 p-5 text-body" aria-labelledby="card-benefits-title">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 id="card-benefits-title" class="font-bold">Lo que recibiste a cambio</h2>
        <p class="mt-1 text-sm text-body-1/60">Beneficios del período seleccionado, por tarjeta.</p>
      </div>
      <label class="flex items-center gap-2 text-xs text-body-1/60">
        <input v-model="includeClosed" type="checkbox" class="h-4 w-4 accent-primary" /> Incluir cerradas antes del período
      </label>
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
      <article v-for="card in visibleCards" :key="card.id" class="rounded-lg border border-base p-4">
        <div class="flex flex-wrap justify-between gap-2">
          <h3 class="font-semibold text-sm">{{ card.name }}</h3>
          <span v-if="card.closed_at" class="text-xs text-body-1/60">Cerrada {{ dateLabel(card.closed_at) }}{{ card.active_in_period ? ' · activa en el período' : '' }}</span>
        </div>
        <dl class="mt-3 space-y-2 text-sm">
          <div class="flex justify-between gap-3"><dt class="text-body-1/60">Descuentos registrados</dt><dd class="text-green-500 tabular-nums">{{ money(discounts(card.id), card.currency) }}</dd></div>
          <div class="flex justify-between gap-3"><dt class="text-body-1/60">Puntos estimados</dt><dd class="tabular-nums">{{ card.points === null ? 'Sin estimación' : card.points.toLocaleString('es') }}</dd></div>
          <div v-if="card.estimated_value !== null" class="flex justify-between gap-3"><dt class="text-body-1/60">Valor de canje estimado</dt><dd>{{ money(card.estimated_value, card.currency) }}</dd></div>
        </dl>
        <p v-if="card.points !== null" class="mt-3 text-xs text-body-1/60">{{ card.rules.points }} puntos por cada {{ money(Number(card.rules.spend), card.currency) }}. Calculados sobre compras verificadas; pueden variar por redondeos, devoluciones y exclusiones del banco.</p>
        <p v-if="card.multi_currency" class="mt-3 text-xs text-body-1/60">La estimación incluye únicamente compras en {{ card.currency }}. Las compras en otras monedas necesitan su propia regla.</p>
        <Link :href="route('finance.accounts.show', card.id)" class="mt-3 inline-block text-xs text-primary underline">Ver tarjeta y configurar beneficios</Link>
      </article>
    </div>
    <p class="mt-4 text-xs text-body-1/60">Los descuentos del ciclo no se clasifican automáticamente como cashback. Los puntos son estimaciones, no el saldo confirmado por el banco.</p>
  </section>
</template>

<style scoped>
.history-panel { border: 1px solid rgba(255,107,128,.3); border-radius: 16px; padding: 30px; background-image: linear-gradient(110deg, rgba(255,107,128,.055), transparent 62%); }
.history-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; }
.history-heading-copy { display: flex; align-items: center; gap: 15px; }
.history-icon { font-size: 27px; }
.history-heading h2 { font-size: 23px; font-weight: 700; letter-spacing: -.5px; line-height: 1.4; }
.history-heading p { font-size: 15px; margin-top: 3px; }
.history-date-badge { color: #64d8ad; background: rgba(100,216,173,.07); border: 1px solid rgba(100,216,173,.25); border-radius: 24px; padding: 5px 12px; font-size: 12px; white-space: nowrap; }
.history-timeline { display: flex; gap: 0; overflow-x: auto; padding: 32px 0 30px; }
.history-event { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; flex: 1 0 170px; min-width: 0; padding: 9px 12px 0; text-align: center; font-size: 13px; color: inherit; opacity: .65; }
.history-event::before { content: ''; position: absolute; left: 0; right: 0; top: 18px; height: 2px; background: rgba(148,163,184,.2); }
.history-event:first-child::before { left: 50%; }
.history-event:last-child::before { right: 50%; }
.history-dot { position: relative; z-index: 1; display: block; flex-shrink: 0; height: 14px; width: 14px; margin: 2px auto 21px; border-radius: 50%; border: 3px solid rgb(var(--c-base-lvl-3)); background: #637489; box-shadow: 0 0 0 1px #637489; }
.history-event strong { display: block; margin-bottom: 6px; font-size: 14px; }
.history-event.is-selected { opacity: 1; }
.history-event.is-selected .history-dot { background: #ff6b80; box-shadow: 0 0 0 5px rgba(255,107,128,.12), 0 0 0 1px #ff6b80; }
.history-event:hover { opacity: 1; }
.history-event:focus-visible { outline: 2px solid #ff6b80; outline-offset: -2px; border-radius: 8px; }
.history-snapshot { display: grid; grid-template-columns: 1.1fr 1fr; gap: 30px; border: 1px solid rgba(148,163,184,.2); border-radius: 15px; padding: 30px; }
.history-kicker { font-size: 14px; }
.history-narrative h3 { font-size: 26px; font-weight: 700; line-height: 1.3; letter-spacing: -.5px; margin: 13px 0 15px; }
.history-narrative > p { font-size: 16px; line-height: 1.7; }
.history-metrics { display: flex; flex-wrap: wrap; gap: 26px; margin-top: 29px; }
.history-metrics strong { display: block; font-size: 24px; font-weight: 700; line-height: 1.35; }
.history-metrics span { display: block; font-size: 13px; margin-top: 6px; }
.history-card-row { display: flex; justify-content: space-between; align-items: baseline; gap: 18px; padding: 17px 0; border-bottom: 1px solid rgba(148,163,184,.18); font-size: 14px; }
.history-card-row > span { text-align: right; }
.history-card-note { display: block; font-size: 12px; margin-top: 4px; }
.history-footnote { font-size: 12px; line-height: 1.6; margin-top: 19px; }
.history-incomplete { padding: 18px 0; }
.history-incomplete-icon { font-size: 26px; }
.history-incomplete h4 { font-size: 17px; font-weight: 600; margin: 10px 0; }
.history-incomplete p { font-size: 14px; line-height: 1.7; }
.history-comparison, .history-completion { margin-top: 24px; border-top-width: 1px; padding-top: 20px; }
.history-comparison summary { font-size: 16px; cursor: pointer; padding: 4px 0; }
.history-completion summary { font-size: 13px; cursor: pointer; padding: 4px 0; }
.history-comparison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 20px; }
.history-comparison-grid article { padding: 20px; border-radius: 10px; }
.history-comparison-grid span { font-size: 13px; }
.history-comparison-grid strong { display: block; font-size: 20px; margin: 10px 0; }
.history-missing-list { display: grid; gap: 8px; margin-top: 14px; }
.history-missing-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; font-size: 13px; }
.history-missing-row button, .history-missing-row a { padding: 10px 0; text-decoration: underline; white-space: nowrap; }
@media (max-width: 900px) { .history-snapshot { grid-template-columns: 1fr; gap: 20px; } }
@media (max-width: 600px) { .history-panel { padding: 20px 16px; } .history-heading { align-items: flex-start; } .history-heading h2 { font-size: 19px; } .history-heading p { font-size: 13px; } .history-heading-copy { gap: 9px; } .history-date-badge { padding: 4px 8px; font-size: 10px; } .history-timeline { padding: 23px 0; } .history-event { flex-basis: 150px; } .history-snapshot { padding: 21px 18px; } .history-narrative h3 { font-size: 23px; } .history-narrative > p { font-size: 14px; } .history-metrics { gap: 20px; } .history-metrics strong { font-size: 21px; } .history-card-row { flex-wrap: wrap; gap: 7px; } .history-comparison-grid { grid-template-columns: 1fr; } }
@media (prefers-reduced-motion: reduce) { .history-event { transition: none; } }
</style>
