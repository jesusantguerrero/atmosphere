<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { VueDraggableNext as Draggable } from 'vue-draggable-next';
import IconDrag from '@/Components/icons/IconDrag.vue';
import AccountModal from '@/domains/transactions/components/AccountModal.vue';
import { useAppContextStore } from '@/store';
import { useAccountsStore } from '@/store/accounts.store';
import { formatMoney } from '@/utils';
import type { IAccount } from '@/domains/transactions/models';

const props = defineProps<{ accounts: IAccount[] }>();
const context = useAppContextStore();
const store = useAccountsStore();
const search = ref('');
const filter = ref('active');
const isReordering = ref(false);
const isSavingOrder = ref(false);
const orderMessage = ref('');
const editing = ref<Partial<IAccount> | null>(null);
const list = computed(() => [...(store.accounts.length ? store.accounts : props.accounts)]
  .sort((a, b) => Number(a.index ?? 0) - Number(b.index ?? 0) || a.id - b.id));
const isArchived = (account: any) => account.status === 'disabled';
const isClosed = (account: any) => Boolean(account.closed_at) || isArchived(account);
const isCard = (account: IAccount) => Number(account.credit_limit) > 0 || Boolean(account.credit_closing_day);
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
const saveOrder = async (accounts: IAccount[]): Promise<void> => {
  if (isSavingOrder.value) return;
  isSavingOrder.value = true;
  orderMessage.value = '';
  if (!store.accounts.length) store.setAccounts(props.accounts);
  try {
    await store.reorder(accounts.map(account => account.id));
    orderMessage.value = 'Orden guardado';
  } catch {
    orderMessage.value = 'No se pudo guardar el orden. Inténtalo de nuevo.';
  } finally {
    isSavingOrder.value = false;
  }
};
const moveAccount = (accounts: IAccount[], position: number, offset: number): void => {
  const ordered = [...accounts];
  const target = position + offset;
  if (target < 0 || target >= ordered.length) return;
  [ordered[position], ordered[target]] = [ordered[target], ordered[position]];
  saveOrder(ordered);
};
const utilization = (account: IAccount): number => Math.min(100, Math.max(0, -Number(account.balance) / Number(account.credit_limit || 1) * 100));
onMounted(() => {
  if (new URLSearchParams(window.location.search).has('newAccount')) editing.value = {};
});
</script>

<template>
  <section class="py-6 text-body">
    <header class="flex flex-wrap items-start justify-between gap-4">
      <div><h2 class="text-2xl font-bold">Tus cuentas</h2><p class="mt-1 text-sm text-body-1/70">Tus saldos de un vistazo. Abre una cuenta para ver sus movimientos.</p></div>
      <div class="flex gap-2">
        <button v-if="list.length > 1" type="button" :aria-pressed="isReordering" :disabled="isSavingOrder" class="min-h-[44px] rounded-lg border border-base px-4 text-sm font-medium hover:bg-base-lvl-3 disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-primary" @click="isReordering = !isReordering">{{ isReordering ? 'Terminar de ordenar' : 'Ordenar' }}</button>
        <button type="button" class="min-h-[44px] rounded-lg bg-primary px-4 font-semibold text-white focus-visible:ring-2 focus-visible:ring-primary" @click="editing = {}">Añadir cuenta</button>
      </div>
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
    <div v-if="isReordering || orderMessage" class="mb-4 flex flex-wrap items-center justify-between gap-2 text-sm text-body-1/75">
      <p v-if="isReordering">Arrastra el asa o usa las flechas. El orden también se aplica al widget.</p>
      <p role="status" aria-live="polite">{{ isSavingOrder ? 'Guardando orden…' : orderMessage }}</p>
    </div>
    <section v-for="group in groups" :key="group.name" class="mb-5 overflow-hidden rounded-xl border border-base bg-base-lvl-3">
      <header class="flex items-baseline gap-2 border-b border-base px-4 py-3"><h3 class="font-semibold">{{ group.name }}</h3><span class="text-sm text-body-1/60">{{ group.accounts.length }}</span></header>
      <Draggable :model-value="group.accounts" :disabled="!isReordering || isSavingOrder" handle=".account-drag-handle" :animation="150" ghost-class="opacity-40" tag="div" @update:model-value="saveOrder">
      <article v-for="(account, position) in group.accounts" :key="account.id" :data-account-id="account.id" class="flex items-start gap-3 border-b border-base p-4 last:border-b-0 hover:bg-base-lvl-2/40">
        <div v-if="isReordering" class="flex shrink-0 flex-col items-center gap-1">
          <button type="button" :aria-label="`Arrastrar ${account.name}`" :disabled="isSavingOrder" class="account-drag-handle flex h-11 w-8 touch-none cursor-grab items-center justify-center rounded-lg text-body-1/70 active:cursor-grabbing focus-visible:ring-2 focus-visible:ring-primary"><IconDrag class="h-6 w-5" /></button>
          <div class="flex gap-1">
            <button type="button" :aria-label="`Subir ${account.name}`" :disabled="position === 0 || isSavingOrder" class="min-h-[44px] rounded px-2 text-lg disabled:opacity-25 focus-visible:ring-2 focus-visible:ring-primary" @click="moveAccount(group.accounts, position, -1)">↑</button>
            <button type="button" :aria-label="`Bajar ${account.name}`" :disabled="position === group.accounts.length - 1 || isSavingOrder" class="min-h-[44px] rounded px-2 text-lg disabled:opacity-25 focus-visible:ring-2 focus-visible:ring-primary" @click="moveAccount(group.accounts, position, 1)">↓</button>
          </div>
        </div>
        <div class="min-w-0 flex-1">
          <Link :href="`/finance/accounts/${account.id}`" class="flex items-start justify-between gap-3 rounded-lg focus-visible:ring-2 focus-visible:ring-primary">
            <div class="flex min-w-0 items-center gap-3">
              <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-lg text-primary sm:flex" aria-hidden="true"><IMdiCreditCardOutline v-if="group.cards" /><IMdiWalletOutline v-else /></span>
              <div class="min-w-0"><p class="truncate text-base font-semibold hover:text-primary">{{ account.name }}</p><p class="mt-1 text-xs text-body-1/70">{{ account.bank_code || account.currency_code }}<span v-if="isClosed(account)"> · {{ status(account) }}</span></p></div>
            </div>
            <div class="shrink-0 text-right"><p class="text-xs text-body-1/65">{{ group.cards ? 'Deuda' : 'Saldo' }}</p><p class="mt-1 text-base font-semibold tabular-nums" :class="Number(account.balance) < 0 && !group.cards ? 'text-error' : 'text-body'">{{ formatMoney(group.cards ? Math.max(0, -Number(account.balance)) : account.balance, account.currency_code) }}</p></div>
          </Link>
          <div v-if="group.cards" class="mt-3 flex flex-col gap-2">
            <div class="flex flex-wrap justify-between gap-2 text-xs text-body-1/75"><span>Disponible {{ formatMoney(Math.max(0, Number(account.credit_limit) + Number(account.balance)), account.currency_code) }}</span><span v-if="account.credit_closing_day">Corte el {{ account.credit_closing_day }}</span></div>
            <div v-if="Number(account.credit_limit) > 0" class="h-1 overflow-hidden rounded-full bg-base-lvl-2" role="progressbar" :aria-label="`Crédito utilizado en ${account.name}`" :aria-valuenow="Math.round(utilization(account))" :aria-valuemin="0" :aria-valuemax="100"><div class="h-full rounded-full" :class="utilization(account) >= 90 ? 'bg-error' : 'bg-primary/70'" :style="{width: `${utilization(account)}%`}" /></div>
          </div>
          <div class="mt-1 flex items-center justify-between gap-3"><span class="text-xs" :class="reconciliation(account) === 'Conciliada' ? 'text-success' : 'text-body-1/70'">{{ reconciliation(account) }}</span><button v-if="!isReordering" type="button" :aria-label="`Editar ${account.name}`" class="min-h-[44px] rounded-lg px-2 text-sm text-body-1/75 hover:bg-base-lvl-2 hover:text-primary focus-visible:ring-2 focus-visible:ring-primary" @click="editing = account">Editar</button></div>
        </div>
      </article>
      </Draggable>
    </section>
    <div v-if="!groups.length" class="rounded-xl border border-dashed border-base px-5 py-12 text-center"><h3 class="font-semibold">{{ list.length ? 'No hay cuentas que coincidan' : 'Agrega tu primera cuenta' }}</h3><p class="mt-2 text-sm text-body-1/70">{{ list.length ? 'Prueba otra búsqueda o cambia el filtro.' : 'Registra tu banco, efectivo o tarjeta para consultar sus movimientos.' }}</p></div>
    <AccountModal v-if="editing" :show="true" :form-data="editing" :max-width="context.isMobile ? 'mobile' : undefined" @close="editing = null" />
  </section>
</template>
