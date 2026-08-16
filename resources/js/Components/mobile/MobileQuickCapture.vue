<template>
    <!-- Floating capture button — offset right, above the bottom bar (Maple-style).
         It floats ABOVE the tab row so it never covers the 5th tab (Dinero). -->
    <button
        class="lg:hidden fixed right-4 z-50 flex items-center justify-center text-white rounded-full shadow-lg w-14 h-14 text-2xl bg-primary ring-4 ring-base-lvl-3 active:scale-95 transition-transform"
        :style="floatStyle"
        :aria-label="$t('Quick capture')"
        @click="toggle"
    >
        <i class="fa fa-plus transition-transform duration-200" :class="{ 'rotate-45': open }" />
    </button>

    <!-- Scrim -->
    <div
        class="lg:hidden fixed inset-0 z-40 bg-black/50 transition-opacity duration-200"
        :class="open ? 'opacity-100' : 'opacity-0 pointer-events-none'"
        @click="close"
    />

    <!-- Capture sheet -->
    <div
        class="lg:hidden fixed inset-x-3 z-50 p-4 border shadow-2xl rounded-2xl bg-base-lvl-3 border-base transition-all duration-200"
        :style="floatStyle"
        :class="open ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4 pointer-events-none'"
    >
        <p class="mb-3 text-xs text-center text-body-1/60">{{ $t('Quick capture') }}</p>
        <div class="grid grid-cols-3 gap-2.5">
            <button
                v-for="action in actions"
                :key="action.key"
                type="button"
                class="flex flex-col items-center gap-2 py-3.5 border rounded-xl bg-base-lvl-2 border-base active:scale-95 transition-transform"
                @click="run(action)"
            >
                <span class="flex items-center justify-center w-10 h-10 rounded-xl" :class="action.wrap">
                    <i class="text-lg" :class="[action.icon, action.color]" />
                </span>
                <span class="text-xs font-medium text-body">{{ $t(action.label) }}</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { TRANSACTION_DIRECTIONS, useTransactionModal } from '@/domains/transactions';

const { DEPOSIT, WITHDRAW } = TRANSACTION_DIRECTIONS;
const { openTransactionModal } = useTransactionModal();

const open = ref(false);
const toggle = () => { open.value = !open.value; };
const close = () => { open.value = false; };

// Both the FAB and the sheet anchor just above the 64px (h-16) bottom bar,
// respecting the iOS safe-area inset so they never sit under the home bar.
const floatStyle = { bottom: 'calc(4rem + env(safe-area-inset-bottom) + 0.75rem)' };

// Order fixed by product: Gasto - Ingreso - Transferencia - Tarea - Evento - Comida.
// The three money actions open the app-wide TransactionModal (mounted in
// AppGlobals); the other three deep-link to their creation surface (no global
// modal exists for them yet - that is a future upgrade).
const actions = [
    { key: 'expense',  label: 'Expense',  icon: 'fa fa-arrow-down',    color: 'text-rose-500',    wrap: 'bg-rose-500/15',    run: () => openTransactionModal({ mode: WITHDRAW }) },
    { key: 'income',   label: 'Income',   icon: 'fa fa-arrow-up',      color: 'text-emerald-500', wrap: 'bg-emerald-500/15', run: () => openTransactionModal({ mode: DEPOSIT }) },
    { key: 'transfer', label: 'Transfer', icon: 'fa fa-exchange-alt',  color: 'text-sky-500',     wrap: 'bg-sky-500/15',     run: () => openTransactionModal({ mode: 'transfer' }) },
    { key: 'task',     label: 'Task',     icon: 'fa fa-check-circle',  color: 'text-teal-500',    wrap: 'bg-teal-500/15',    run: () => router.visit('/housing/chores') },
    { key: 'event',    label: 'Event',    icon: 'fa fa-calendar-plus', color: 'text-amber-500',   wrap: 'bg-amber-500/15',   run: () => router.visit('/calendar') },
    { key: 'meal',     label: 'Meal',     icon: 'fa fa-utensils',      color: 'text-violet-500',  wrap: 'bg-violet-500/15',  run: () => router.visit('/meal-planner') },
];

const run = (action) => {
    close();
    action.run();
};
</script>
