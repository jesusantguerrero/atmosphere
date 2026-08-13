<script setup lang="ts">
/**
 * ChoresScreen — a chrome-less, bookmarkable "kitchen screen" for chores.
 * Reuses the exact FamilyView component (no logic duplication); this page just
 * drops the app shell and enlarges it for a tablet/TV left open in the kitchen.
 */
import { computed, provide } from 'vue';
import { router } from '@inertiajs/vue3';
import FamilyView from '@/Components/board/views/FamilyView.vue';
import { useDarkMode } from '@/composables/useDarkMode';

const props = defineProps<{ chores: any[]; users: any[] }>();
const board = computed(() => props.chores?.[0]);
provide('users', props.users);
useDarkMode(); // apply the app's light/dark theme on this chrome-less page

const todayLabel = new Date().toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' });
</script>

<template>
  <div class="min-h-screen bg-base-lvl-1 text-body">
    <header class="flex items-center justify-between px-8 py-5 border-b border-base">
      <div class="flex items-center gap-3">
        <span class="flex items-center justify-center w-11 h-11 rounded-xl bg-primary/15 text-primary">
          <i class="fa fa-home"></i>
        </span>
        <div>
          <h1 class="text-2xl font-black leading-none">{{ $t('Chores') }}</h1>
          <p class="mt-1 text-sm capitalize text-body-1/60">{{ todayLabel }}</p>
        </div>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold transition border rounded-full border-base text-body-1/70 hover:bg-base-lvl-2"
        @click="router.visit('/housing/chores')"
      >
        <i class="fa fa-times"></i>
        {{ $t('Exit') }}
      </button>
    </header>

    <main class="px-8 py-8" style="zoom: 1.15">
      <FamilyView v-if="board" :stages="board.stages" :fields="board.fields" :board-id="board.id" :kiosk="true" />
      <p v-else class="py-24 text-lg text-center text-body-1/50">{{ $t('No chores yet') }}</p>
    </main>
  </div>
</template>
