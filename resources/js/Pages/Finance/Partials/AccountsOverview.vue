<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AccountModal from '@/domains/transactions/components/AccountModal.vue';
import { useAppContextStore } from '@/store';
import { useAccountsStore } from '@/store/accounts.store';
import { formatMoney } from '@/utils';
import type { IAccount } from '@/domains/transactions/models';

const props = defineProps<{ accounts: IAccount[] }>();
const context = useAppContextStore();
const store = useAccountsStore();
const search = ref('');
const filter = ref('all');
const editing = ref<Partial<IAccount> | null>(null);
const list = computed(() => store.accounts.length ? store.accounts : props.accounts);
const isArchived = (account: any) => account.archived === true || account.archived === 1 || account.archived === '1';
const isClosed = (account: any) => Boolean(account.closed_at) || isArchived(account);
const isCard = (account: IAccount) => Boolean(account.credit_closing_day);
const status = (account: any) => account.closed_at ? 'Cerrada' : isArchived(account) ? 'Archivada' : 'Activa';
const filtered = computed(() => list.value.filter(account => {
  const matchesType = filter.value === 'all' || (filter.value === 'closed' ? isClosed(account) : !isClosed(account) && (filter.value !== 'cards' || isCard(account)));
  return matchesType && `${account.name} ${account.bank_code ?? ''}`.toLocaleLowerCase().includes(search.value.toLocaleLowerCase().trim());
}));
const groups = computed(() => [
  { name: 'Tarjetas de crédito', accounts: filtered.value.filter(isCard), cards: true },
  { name: 'Otras cuentas', accounts: filtered.value.filter(account => !isCard(account)), cards: false },
].filter(group => group.accounts.length));
const summaries = computed(() => {
  const totals: Record<string, { cash: number; debt: number; available: number }> = {};
  list.value.filter(account => !isClosed(account)).forEach(account => {
    const currency = account.currency_code;
    const total = totals[currency] ||= { cash: 0, debt: 0, available: 0 };
    const balance = Number(account.balance) || 0;
    if (isCard(account)) {
      total.debt += Math.max(0, -balance);
      total.available += Math.max(0, Number(account.credit_limit) + balance);
    } else {
      total.cash += balance;
    }
  });
  return Object.entries(totals).map(([currency, totals]) => ({ currency, ...totals }));
});
const reconciliation = (account: any) => {
  const last = account.reconciliation_last;
  if (last?.status === 'pending') return 'Conciliación pendiente';
  if (last?.status === 'completed' && Math.abs(Number(last.amount) - Number(account.balance)) < 0.01) return 'Conciliada';
  return 'Por revisar';
};
onMounted(() => {
  if (new URLSearchParams(window.location.search).has('newAccount')) editing.value = {};
});
</script>

<template>
  <section class="py-6 text-body">
    <header class="flex flex-wrap items-start justify-between gap-4">
      <div><h2 class="text-2xl font-bold">Tus cuentas</h2><p class="mt-1 text-sm text-body-1/70">Consulta tus saldos y administra bancos, efectivo y tarjetas.</p></div>
      <button type="button" class="min-h-[44px] rounded-lg bg-primary px-4 font-semibold text-white focus-visible:ring-2 focus-visible:ring-primary" @click="editing = {}">Añadir cuenta</button>
    </header>
    <dl v-for="summary in summaries" :key="summary.currency" class="mt-6 grid gap-5 border-y border-base py-5 sm:grid-cols-3">
      <div><dt class="text-sm text-body-1/70">Saldo en otras cuentas · {{ summary.currency }}</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ formatMoney(summary.cash, summary.currency) }}</dd></div>
      <div><dt class="text-sm text-body-1/70">Deuda en tarjetas</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ formatMoney(summary.debt, summary.currency) }}</dd></div>
      <div><dt class="text-sm text-body-1/70">Crédito disponible</dt><dd class="mt-1 text-xl font-semibold tabular-nums">{{ formatMoney(summary.available, summary.currency) }}</dd></div>
    </dl>
    <div class="my-6 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap gap-2" aria-label="Filtrar cuentas"><button v-for="option in [{id:'all',name:'Todas'},{id:'active',name:'Activas'},{id:'cards',name:'Tarjetas'},{id:'closed',name:'Cerradas y archivadas'}]" :key="option.id" type="button" :aria-pressed="filter === option.id" class="min-h-[44px] rounded-lg px-3 text-sm focus-visible:ring-2 focus-visible:ring-primary" :class="filter === option.id ? 'bg-base-lvl-3 font-semibold text-body' : 'text-body-1/70 hover:bg-base-lvl-3'" @click="filter = option.id">{{ option.name }}</button></div>
      <input v-model="search" type="search" aria-label="Buscar cuenta" placeholder="Buscar por cuenta o banco" class="min-h-[44px] w-full rounded-lg border border-base bg-base-lvl-2 px-3 text-sm focus:ring-primary sm:w-72" />
    </div>
    <section v-for="group in groups" :key="group.name" class="mb-7 overflow-hidden rounded-xl border border-base bg-base-lvl-3">
      <header class="flex items-baseline gap-2 border-b border-base px-5 py-4"><h3 class="font-semibold">{{ group.name }}</h3><span class="text-sm text-body-1/60">{{ group.accounts.length }}</span></header>
      <div class="hidden grid-cols-[minmax(0,2fr)_1fr_1fr_auto] gap-4 border-b border-base px-5 py-3 text-xs text-body-1/70 lg:grid"><span>Cuenta</span><span class="text-right">Saldo</span><span>{{ group.cards ? 'Límite y corte' : 'Conciliación' }}</span><span class="w-20">Acciones</span></div>
      <article v-for="account in group.accounts" :key="account.id" class="grid items-center gap-3 border-b border-base px-5 py-4 last:border-b-0 lg:grid-cols-[minmax(0,2fr)_1fr_1fr_auto] lg:gap-4">
        <div class="min-w-0"><Link :href="`/finance/accounts/${account.id}`" class="inline-block text-base font-semibold hover:text-primary focus-visible:ring-2 focus-visible:ring-primary">{{ account.name }}</Link><p class="mt-1 text-xs text-body-1/70">{{ status(account) }}<span v-if="group.cards"> · {{ reconciliation(account) }}</span></p></div>
        <div class="font-semibold tabular-nums lg:text-right"><span class="mr-2 text-xs font-normal text-body-1/70 lg:hidden">Saldo</span>{{ formatMoney(account.balance, account.currency_code) }}</div>
        <div class="text-sm text-body-1/70"><template v-if="group.cards"><span class="tabular-nums">{{ formatMoney(account.credit_limit, account.currency_code) }}</span><p class="mt-1 text-xs">Corte el {{ account.credit_closing_day }}</p></template><span v-else>{{ reconciliation(account) }}</span></div>
        <button type="button" :aria-label="`Editar ${account.name}`" class="min-h-[44px] w-20 rounded-lg border border-base text-sm hover:bg-base-lvl-2 focus-visible:ring-2 focus-visible:ring-primary" @click="editing = account">Editar</button>
      </article>
    </section>
    <div v-if="!groups.length" class="rounded-xl border border-dashed border-base px-5 py-12 text-center"><h3 class="font-semibold">{{ list.length ? 'No hay cuentas que coincidan' : 'Agrega tu primera cuenta' }}</h3><p class="mt-2 text-sm text-body-1/70">{{ list.length ? 'Prueba otra búsqueda o cambia el filtro.' : 'Registra tu banco, efectivo o tarjeta para consultar sus movimientos.' }}</p></div>
    <AccountModal v-if="editing" :show="true" :form-data="editing" :max-width="context.isMobile ? 'mobile' : undefined" @close="editing = null" />
  </section>
</template>
