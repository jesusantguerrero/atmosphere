<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
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
let orderMessageTimer: ReturnType<typeof setTimeout> | undefined;
const clearOrderMessageTimer = (): void => {
  if (orderMessageTimer !== undefined) clearTimeout(orderMessageTimer);
};
onUnmounted(clearOrderMessageTimer);
const collapsedGroups = ref<Record<string, boolean>>({});
const toggleGroup = (name: string): void => {
  collapsedGroups.value[name] = !collapsedGroups.value[name];
};
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
  clearOrderMessageTimer();
  orderMessage.value = '';
  if (!store.accounts.length) store.setAccounts(props.accounts);
  try {
    await store.reorder(accounts.map(account => account.id));
    orderMessage.value = 'Orden guardado';
    orderMessageTimer = setTimeout(() => { orderMessage.value = ''; }, 3000);
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
onMounted(() => {
  if (new URLSearchParams(window.location.search).has('newAccount')) editing.value = {};
});
</script>

<template>
  <section class="flex flex-col gap-3 py-3 text-body">
    <header class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-lg font-semibold tracking-tight">Tus cuentas <span class="ml-1 text-sm font-normal text-body-1">{{ filtered.length }}</span></h2>
      <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
        <Link :href="route('finance.transactions')" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg border border-body-1/20 px-4 text-sm font-medium hover:bg-base-lvl-3 focus-visible:ring-2 focus-visible:ring-primary sm:w-auto">Ver todas las transacciones</Link>
        <button v-if="list.length > 1" type="button" :aria-pressed="isReordering" :disabled="isSavingOrder" class="min-h-[44px] rounded-lg border border-body-1/20 px-4 text-sm font-medium hover:bg-base-lvl-3 disabled:opacity-50 focus-visible:ring-2 focus-visible:ring-primary" @click="isReordering = !isReordering">{{ isReordering ? 'Terminar de ordenar' : 'Ordenar' }}</button>
        <button type="button" class="min-h-[44px] rounded-lg bg-primary px-4 font-semibold text-white focus-visible:ring-2 focus-visible:ring-primary" @click="editing = {}">Añadir cuenta</button>
      </div>
    </header>
    <dl v-for="summary in summaries" :key="summary.currency" class="grid grid-cols-2 gap-x-4 gap-y-3 rounded-lg border border-body-1/15 bg-base-lvl-2 px-4 py-3 sm:grid-cols-3">
      <div class="col-span-2 sm:col-span-1"><dt class="text-xs text-body-1">Saldo en cuentas · {{ summary.currency }}</dt><dd class="mt-1 text-lg font-semibold tabular-nums tracking-tight">{{ formatMoney(summary.cash, summary.currency) }}</dd></div>
      <div><dt class="text-xs text-body-1">Deuda en tarjetas</dt><dd class="mt-1 text-base font-semibold tabular-nums sm:text-lg">{{ formatMoney(summary.debt, summary.currency) }}</dd></div>
      <div><dt class="text-xs text-body-1">Crédito disponible</dt><dd class="mt-1 text-base font-semibold tabular-nums sm:text-lg">{{ formatMoney(summary.available, summary.currency) }}</dd></div>
    </dl>
    <div class="flex flex-wrap items-center justify-between gap-2">
      <select v-model="filter" aria-label="Filtrar cuentas" class="min-h-[44px] w-full rounded-lg border border-body-1/20 bg-base-lvl-2 px-3 text-sm text-body focus:ring-primary sm:hidden"><option value="all">Todas las cuentas</option><option value="active">Cuentas activas</option><option value="cards">Tarjetas de crédito</option><option value="closed">Cerradas y archivadas</option></select>
      <div class="hidden max-w-full gap-1 overflow-x-auto sm:flex" aria-label="Filtrar cuentas"><button v-for="option in [{id:'all',name:'Todas'},{id:'active',name:'Activas'},{id:'cards',name:'Tarjetas'},{id:'closed',name:'Cerradas y archivadas'}]" :key="option.id" type="button" :aria-pressed="filter === option.id" class="min-h-[44px] shrink-0 rounded-lg px-3 text-sm focus-visible:ring-2 focus-visible:ring-primary" :class="filter === option.id ? 'bg-base-lvl-3 font-semibold text-body' : 'text-body-1 hover:bg-base-lvl-3'" @click="filter = option.id">{{ option.name }}</button></div>
      <input v-model="search" type="search" aria-label="Buscar cuenta" placeholder="Buscar por cuenta o banco" class="min-h-[44px] w-full rounded-lg border border-body-1/20 bg-base-lvl-2 px-3 text-sm focus:ring-primary sm:w-72" />
    </div>
    <div v-if="isReordering || orderMessage" class="mb-4 flex flex-wrap items-center justify-between gap-2 text-sm text-body-1/75">
      <p v-if="isReordering">Arrastra el asa o usa las flechas. El orden también se aplica al widget.</p>
      <p role="status" aria-live="polite">{{ isSavingOrder ? 'Guardando orden…' : orderMessage }}</p>
    </div>
    <section v-for="(group, groupIndex) in groups" :key="group.name" class="overflow-hidden rounded-lg border border-body-1/15">
      <h3>
        <button type="button" :aria-expanded="!collapsedGroups[group.name]" :aria-controls="`account-group-${groupIndex}`" class="flex min-h-[44px] w-full items-center gap-2 px-3 bg-base-lvl-2 text-left text-sm font-semibold hover:bg-base-lvl-3 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary" @click="toggleGroup(group.name)">
          <svg class="h-4 w-4 shrink-0 text-body-1 transition-transform motion-reduce:transition-none" :class="collapsedGroups[group.name] ? '-rotate-90' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
          {{ group.name }}<span class="rounded bg-base-lvl-3 px-1.5 py-0.5 text-xs font-medium tabular-nums text-body-1">{{ group.accounts.length }}</span>
        </button>
      </h3>
      <Draggable :id="`account-group-${groupIndex}`" v-show="!collapsedGroups[group.name]" :model-value="group.accounts" :disabled="!isReordering || isSavingOrder" handle=".account-drag-handle" :animation="150" ghost-class="opacity-40" tag="div" @update:model-value="saveOrder">
      <article v-for="(account, position) in group.accounts" :key="account.id" :data-account-id="account.id" class="flex min-h-[64px] items-center gap-2 border-t border-body-1/10 px-3 py-2 hover:bg-base-lvl-3/70">
        <div v-if="isReordering" class="flex shrink-0 items-center gap-1">
          <button type="button" :aria-label="`Arrastrar ${account.name}`" :disabled="isSavingOrder" class="account-drag-handle flex h-11 w-8 touch-none cursor-grab items-center justify-center rounded-lg text-body-1 active:cursor-grabbing focus-visible:ring-2 focus-visible:ring-primary"><IconDrag class="h-6 w-5" /></button>
          <div class="flex flex-col">
            <button type="button" :aria-label="`Subir ${account.name}`" :disabled="position === 0 || isSavingOrder" class="min-h-[32px] rounded px-2 text-lg disabled:opacity-25 focus-visible:ring-2 focus-visible:ring-primary" @click="moveAccount(group.accounts, position, -1)">↑</button>
            <button type="button" :aria-label="`Bajar ${account.name}`" :disabled="position === group.accounts.length - 1 || isSavingOrder" class="min-h-[32px] rounded px-2 text-lg disabled:opacity-25 focus-visible:ring-2 focus-visible:ring-primary" @click="moveAccount(group.accounts, position, 1)">↓</button>
          </div>
        </div>
          <Link :href="`/finance/accounts/${account.id}`" class="grid min-h-[44px] min-w-0 flex-1 grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 rounded focus-visible:ring-2 focus-visible:ring-primary sm:grid-cols-[minmax(0,1fr)_minmax(9rem,0.6fr)_7rem]">
            <div class="flex min-w-0 items-center gap-2.5">
              <span class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-base-lvl-3 text-base text-body-1 sm:flex" aria-hidden="true"><IMdiCreditCardOutline v-if="group.cards" /><IMdiWalletOutline v-else /></span>
              <div class="min-w-0"><p class="truncate text-sm font-medium hover:text-primary">{{ account.name }}</p><p class="mt-0.5 truncate text-xs text-body-1">{{ account.bank_code || account.currency_code }}<span v-if="isClosed(account)"> · {{ status(account) }}</span><span v-else-if="group.cards && account.credit_closing_day"> · Corte {{ account.credit_closing_day }}</span><span v-else class="sm:hidden"> · {{ reconciliation(account) }}</span></p></div>
            </div>
            <div class="text-right"><p class="text-sm font-medium tabular-nums" :class="Number(account.balance) < 0 && !group.cards ? 'text-error' : 'text-body'">{{ formatMoney(group.cards ? Math.max(0, -Number(account.balance)) : account.balance, account.currency_code) }}</p><p v-if="group.cards" class="mt-0.5 text-xs text-body-1">Disp. {{ formatMoney(Math.max(0, Number(account.credit_limit) + Number(account.balance)), account.currency_code) }}</p></div>
            <span class="hidden text-right text-xs sm:block" :class="reconciliation(account) === 'Conciliada' ? 'text-success' : 'text-body-1'">{{ reconciliation(account) }}</span>
          </Link>
          <button v-if="!isReordering" type="button" :aria-label="`Editar ${account.name}`" :title="`Editar ${account.name}`" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-body-1 hover:bg-base-lvl-3 hover:text-primary focus-visible:ring-2 focus-visible:ring-primary" @click="editing = account"><IMdiPencilOutline class="h-4 w-4" /></button>
      </article>
      </Draggable>
    </section>
    <div v-if="!groups.length" class="rounded-xl border border-dashed border-base px-5 py-12 text-center"><h3 class="font-semibold">{{ list.length ? 'No hay cuentas que coincidan' : 'Agrega tu primera cuenta' }}</h3><p class="mt-2 text-sm text-body-1">{{ list.length ? 'Prueba otra búsqueda o cambia el filtro.' : 'Registra tu banco, efectivo o tarjeta para consultar sus movimientos.' }}</p></div>
    <AccountModal v-if="editing" :show="true" :form-data="editing" :max-width="context.isMobile ? 'mobile' : undefined" @close="editing = null" />
  </section>
</template>
