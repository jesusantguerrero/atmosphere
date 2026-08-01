<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";

interface Block { id: number; title: string; start: string; end: string; color: string; member_id: number | null; }

const current = ref<Block | null>(null);
const next = ref<Block | null>(null);
const loaded = ref(false);

onMounted(async () => {
  try {
    const { data } = await axios.get("/housing/routine/current");
    current.value = data.current;
    next.value = data.next;
  } catch (e) {
    // silent — routine may not exist yet
  }
  loaded.value = true;
});
</script>

<template>
  <div
    v-if="loaded && (current || next)"
    class="bg-base-lvl-3 border border-base rounded-xl p-3 flex gap-3 items-stretch cursor-pointer hover:border-base-lvl-1 transition"
    @click="router.visit('/housing/routine')"
  >
    <div class="flex-1 min-w-0">
      <div class="text-[10px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Now') }}</div>
      <div v-if="current" class="flex items-stretch gap-2">
        <span class="w-1 rounded" :style="{ background: current.color }"></span>
        <div class="min-w-0">
          <div class="text-sm font-bold text-body truncate">{{ current.title }}</div>
          <div class="text-[11px] text-body-1/60">{{ current.start }}–{{ current.end }}</div>
        </div>
      </div>
      <div v-else class="text-xs text-body-1/40 py-1">{{ $t('Free time') }}</div>
    </div>
    <div class="w-px bg-base"></div>
    <div class="flex-1 min-w-0">
      <div class="text-[10px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Next') }}</div>
      <div v-if="next" class="flex items-stretch gap-2">
        <span class="w-1 rounded" :style="{ background: next.color }"></span>
        <div class="min-w-0">
          <div class="text-sm font-bold text-body truncate">{{ next.title }}</div>
          <div class="text-[11px] text-body-1/60">{{ next.start }}</div>
        </div>
      </div>
      <div v-else class="text-xs text-body-1/40 py-1">{{ $t('Nothing left today') }}</div>
    </div>
  </div>
</template>
