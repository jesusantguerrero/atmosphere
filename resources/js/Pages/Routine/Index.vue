<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from "vue";
import axios from "axios";
import AppLayout from "@/Components/templates/AppLayout.vue";
import HouseSectionNav from "@/Components/templates/HouseSectionNav.vue";

interface Block {
  id: number;
  day: number;            // 0=Mon .. 6=Sun
  title: string;
  start: string;          // "HH:MM"
  end: string;
  color: string;
  member_id: number | null;
  note: string;
}
interface Member { id: number; name: string; }
interface PlanPayload { id: number; blocks: Block[]; days: string[]; }

const props = defineProps<{ plan: PlanPayload; members: Member[] }>();

const blocks = ref<Block[]>([...props.plan.blocks]);
const SHORT = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
const HOUR_H = 40;
const COLORS = ["#56C08A", "#6E9BE6", "#E8A54F", "#E87FA8", "#A98BE0", "#B79B82", "#5CC2C2", "#E0736F", "#6B7686"];

const toMin = (t: string) => {
  const [h, m] = String(t || "0:0").split(":");
  return (parseInt(h) || 0) * 60 + (parseInt(m) || 0);
};

// Time range auto-expands to fit the blocks (early risers / night owls) with a
// sensible default window of 05:00–23:00; clamped to a valid 0–24 day.
const DEFAULT_START = 5;
const DEFAULT_END = 23;
const startH = computed(() => {
  let min = DEFAULT_START;
  for (const b of blocks.value) min = Math.min(min, Math.floor(toMin(b.start) / 60));
  return Math.max(0, min);
});
const endH = computed(() => {
  let max = DEFAULT_END;
  for (const b of blocks.value) max = Math.max(max, Math.ceil(toMin(b.end) / 60));
  return Math.min(24, max);
});
const hours = computed(() => Array.from({ length: endH.value - startH.value }, (_, i) => startH.value + i));
const gridHeight = computed(() => hours.value.length * HOUR_H);

// Member filter
const filterMember = ref<number | null>(null);
const shown = (b: Block) => filterMember.value === null || b.member_id === filterMember.value;
const memberName = (id: number | null) => props.members.find((m) => m.id === id)?.name ?? "";
const memberColor = (id: number | null) => {
  if (id === null) return "#69727F";
  const idx = props.members.findIndex((m) => m.id === id);
  return COLORS[(idx + 3) % COLORS.length];
};
const initial = (id: number | null) => (memberName(id) || "?").slice(0, 1).toUpperCase();

const blocksByDay = (day: number) =>
  blocks.value.filter((b) => b.day === day).sort((a, b) => toMin(a.start) - toMin(b.start));

const blockStyle = (b: Block) => {
  const top = ((toMin(b.start) - startH.value * 60) / 60) * HOUR_H;
  const height = Math.max(18, ((toMin(b.end) - toMin(b.start)) / 60) * HOUR_H - 3);
  return {
    top: `${top}px`,
    height: `${height}px`,
    background: `${b.color}22`,
    borderLeft: `3px solid ${b.color}`,
    color: b.color,
  };
};

// Now-line (browser-local)
const now = new Date();
const nowDow = (now.getDay() + 6) % 7;            // 0=Mon..6=Sun
const nowTop = computed(() => ((now.getHours() * 60 + now.getMinutes() - startH.value * 60) / 60) * HOUR_H);
const nowVisible = computed(() => now.getHours() >= startH.value && now.getHours() < endH.value);

// ---- Modal ----
const showModal = ref(false);
const saving = ref(false);
const blankForm = (day = 0): Block =>
  ({ id: 0, day, title: "", start: "09:00", end: "10:00", color: COLORS[1], member_id: null, note: "" });
const form = ref<Block>(blankForm());
const isEdit = computed(() => form.value.id > 0);

const openNew = (day = 0) => { form.value = blankForm(day); showModal.value = true; };
const openEdit = (b: Block) => { form.value = { ...b }; showModal.value = true; };

// Duplicate: turn the current edit into a fresh (unsaved) copy — Save creates a
// new block with the same title/time/color/member/note that the user can retime.
const duplicate = () => { form.value = { ...form.value, id: 0 }; };

const payloadOf = (b: Block) => ({
  day: b.day, title: b.title.trim(), start: b.start, end: b.end,
  color: b.color, member_id: b.member_id, note: b.note ?? "",
});

const save = async () => {
  if (!form.value.title.trim() || saving.value) return;
  saving.value = true;
  try {
    if (isEdit.value) {
      const { data } = await axios.put(`/housing/routine/${props.plan.id}/blocks/${form.value.id}`, payloadOf(form.value));
      const i = blocks.value.findIndex((b) => b.id === data.id);
      if (i !== -1) blocks.value[i] = data;
    } else {
      const { data } = await axios.post(`/housing/routine/${props.plan.id}/blocks`, payloadOf(form.value));
      blocks.value.push(data);
    }
    showModal.value = false;
  } finally {
    saving.value = false;
  }
};

// ---- Undo-able delete ----
let toastTimer: ReturnType<typeof setTimeout> | null = null;
const toast = ref<{ block: Block } | null>(null);
const clearToast = () => { if (toastTimer) clearTimeout(toastTimer); toastTimer = null; toast.value = null; };

const remove = async () => {
  if (!isEdit.value) return;
  const removed = { ...form.value };
  blocks.value = blocks.value.filter((b) => b.id !== removed.id);
  showModal.value = false;
  await axios.delete(`/housing/routine/${props.plan.id}/blocks/${removed.id}`).catch(() => {});
  clearToast();
  toast.value = { block: removed };
  toastTimer = setTimeout(clearToast, 5000);
};

const undoDelete = async () => {
  if (!toast.value) return;
  const b = toast.value.block;
  clearToast();
  const { data } = await axios.post(`/housing/routine/${props.plan.id}/blocks`, payloadOf(b));
  blocks.value.push(data);
};

// ---- Quick member assign (per block, floating at click coords) ----
const assignMenu = ref<{ x: number; y: number; block: Block } | null>(null);
const openAssign = (b: Block, ev: MouseEvent) => {
  if (assignMenu.value?.block.id === b.id) { assignMenu.value = null; return; }
  const menuW = 150, menuH = 44 + props.members.length * 30;
  const x = Math.max(8, Math.min(ev.clientX, window.innerWidth - menuW));
  const y = Math.min(ev.clientY, window.innerHeight - menuH - 8);
  assignMenu.value = { x, y, block: b };
};
const setBlockMember = async (b: Block, memberId: number | null) => {
  assignMenu.value = null;
  if (b.member_id === memberId) return;
  const next = { ...b, member_id: memberId };
  const { data } = await axios.put(`/housing/routine/${props.plan.id}/blocks/${b.id}`, payloadOf(next));
  const i = blocks.value.findIndex((x) => x.id === data.id);
  if (i !== -1) blocks.value[i] = data;
};

// ---- Per-day dialog: duplicate day / assign day ----
const dayMenu = ref<number | null>(null);
const copyTargets = ref<number[]>([]);
const copyMode = ref<"replace" | "append">("replace");
const busyDay = ref(false);

const openDay = (day: number) => {
  dayMenu.value = day;
  copyTargets.value = [];
  copyMode.value = "replace";
};
const toggleTarget = (day: number) => {
  const i = copyTargets.value.indexOf(day);
  if (i === -1) copyTargets.value.push(day); else copyTargets.value.splice(i, 1);
};

const applyDay = async (from: number) => {
  if (!copyTargets.value.length || busyDay.value) return;
  busyDay.value = true;
  try {
    const { data } = await axios.post(`/housing/routine/${props.plan.id}/copy-day`, {
      from, to: copyTargets.value, mode: copyMode.value,
    });
    const replaced: number[] = data.replaced ?? [];
    if (replaced.length) blocks.value = blocks.value.filter((b) => !replaced.includes(b.day));
    for (const nb of (data.blocks ?? [])) blocks.value.push(nb);
    dayMenu.value = null;
  } finally {
    busyDay.value = false;
  }
};

const assignDay = async (day: number, memberId: number | null) => {
  if (busyDay.value) return;
  busyDay.value = true;
  try {
    await axios.post(`/housing/routine/${props.plan.id}/assign-day`, { day, member_id: memberId });
    blocks.value = blocks.value.map((b) => (b.day === day ? { ...b, member_id: memberId } : b));
    dayMenu.value = null;
  } finally {
    busyDay.value = false;
  }
};

onBeforeUnmount(() => { if (toastTimer) clearTimeout(toastTimer); });
</script>

<template>
  <AppLayout :title="$t('Routine')">
    <template #header>
      <HouseSectionNav />
    </template>

    <div class="px-5 pb-20 mx-auto pt-16 max-w-6xl">
      <!-- header: filter + add -->
      <div class="flex items-center gap-2 mb-4 flex-wrap">
        <h1 class="text-lg font-bold text-body mr-auto">{{ $t('Weekly routine') }}</h1>
        <button
          class="text-xs font-semibold px-3 py-1.5 rounded-full border transition"
          :class="filterMember === null ? 'bg-base-lvl-3 text-body border-base-lvl-1' : 'bg-base-lvl-1 text-body-1/70 border-base'"
          @click="filterMember = null"
        >{{ $t('Everyone') }}</button>
        <button
          v-for="m in members" :key="m.id"
          class="text-xs font-semibold px-3 py-1.5 rounded-full border transition flex items-center gap-1.5"
          :class="filterMember === m.id ? 'bg-base-lvl-3 text-body border-base-lvl-1' : 'bg-base-lvl-1 text-body-1/70 border-base'"
          @click="filterMember = m.id"
        >
          <span class="w-2 h-2 rounded-full" :style="{ background: memberColor(m.id) }"></span>{{ m.name }}
        </button>
        <button
          class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-primary text-white ml-1"
          @click="openNew()"
        >+ {{ $t('Block') }}</button>
      </div>

      <!-- grid -->
      <div class="bg-base-lvl-3 border border-base rounded-xl overflow-hidden">
        <div class="grid border-b border-base bg-base-lvl-2" style="grid-template-columns:44px repeat(7,1fr)">
          <div></div>
          <div v-for="(d, i) in SHORT" :key="i" class="relative py-2 text-center text-[11px] font-bold"
               :class="i === nowDow ? 'text-body' : 'text-body-1/60'">
            {{ d }}
            <button
              class="absolute right-1 top-1.5 w-4 h-4 rounded text-body-1/40 hover:text-body hover:bg-base-lvl-1 leading-none"
              @click.stop="openDay(i)"
              :title="$t('Day options')"
            >⋯</button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <div class="grid relative" style="grid-template-columns:44px repeat(7,1fr);min-width:760px">
            <!-- time gutter -->
            <div class="relative" :style="{ height: gridHeight + 'px' }">
              <div v-for="h in hours" :key="h" class="absolute right-1.5 text-[9px] text-body-1/40"
                   :style="{ top: ((h - startH) * HOUR_H - 5) + 'px' }">{{ (h < 10 ? '0' : '') + h }}:00</div>
            </div>
            <!-- day columns -->
            <div v-for="day in 7" :key="day" class="relative border-l border-base/50"
                 :style="{ height: gridHeight + 'px' }"
                 @dblclick="openNew(day - 1)">
              <!-- hour lines -->
              <div v-for="h in hours" :key="h" class="absolute left-0 right-0 border-t border-base/30"
                   :style="{ top: ((h - startH) * HOUR_H) + 'px' }"></div>
              <!-- now line -->
              <div v-if="nowVisible && (day - 1) === nowDow" class="absolute left-0 right-0 h-0.5 bg-primary z-10"
                   :style="{ top: nowTop + 'px' }"></div>
              <!-- blocks -->
              <div v-for="b in blocksByDay(day - 1)" :key="b.id"
                   class="absolute left-0.5 right-0.5 rounded-md px-1.5 py-0.5 text-[10px] font-semibold overflow-hidden cursor-pointer leading-tight transition"
                   :class="{ 'opacity-20 grayscale': !shown(b) }"
                   :style="blockStyle(b)"
                   :title="b.note || b.title"
                   @click="openEdit(b)">
                <div class="truncate flex items-center gap-1">
                  {{ b.title }}
                  <span v-if="b.note" class="opacity-60 text-[8px]">✎</span>
                </div>
                <div class="text-[8px] opacity-70">{{ b.start }}–{{ b.end }}</div>
                <button
                  class="absolute top-0.5 right-0.5 w-3.5 h-3.5 rounded-full text-[7px] flex items-center justify-center font-bold text-white ring-1 ring-white/30"
                  :style="{ background: memberColor(b.member_id) }"
                  @click.stop="openAssign(b, $event)"
                  :title="$t('Assign member')"
                >{{ b.member_id ? initial(b.member_id) : '+' }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p class="text-[11px] text-body-1/50 mt-3">{{ $t('Double-tap a day to add · tap a block to edit · ⋯ to duplicate a day. This template repeats every week.') }}</p>
    </div>

    <!-- quick-assign floating menu -->
    <Teleport to="body">
      <div v-if="assignMenu" class="fixed inset-0 z-40" @click="assignMenu = null">
        <div class="absolute w-36 bg-base-lvl-3 border border-base rounded-lg shadow-2xl p-1 text-body"
             :style="{ left: assignMenu.x + 'px', top: (assignMenu.y + 6) + 'px' }" @click.stop>
          <button type="button" class="w-full text-left text-xs px-2 py-1 rounded hover:bg-base-lvl-2 text-body-1/80"
                  @click="setBlockMember(assignMenu.block, null)">{{ $t('Everyone') }}</button>
          <button v-for="m in members" :key="m.id" type="button"
                  class="w-full flex items-center gap-1.5 text-left text-xs px-2 py-1 rounded hover:bg-base-lvl-2"
                  @click="setBlockMember(assignMenu.block, m.id)">
            <span class="w-2 h-2 rounded-full" :style="{ background: memberColor(m.id) }"></span>{{ m.name }}
          </button>
        </div>
      </div>
    </Teleport>

    <!-- per-day dialog -->
    <Teleport to="body">
      <div v-if="dayMenu !== null" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/50" @click.self="dayMenu = null">
        <div class="w-full max-w-xs bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-4">
          <h3 class="font-bold text-body mb-3">{{ SHORT[dayMenu] }} · {{ $t('Day options') }}</h3>

          <p class="text-[10px] uppercase tracking-wide text-body-1/50 mb-1.5">{{ $t('Duplicate day to') }}</p>
          <div class="flex flex-wrap gap-1.5 mb-2">
            <button v-for="(dd, di) in SHORT" :key="di"
                    v-show="di !== dayMenu"
                    type="button"
                    class="text-xs px-2 py-1 rounded-lg border transition"
                    :class="copyTargets.includes(di) ? 'bg-primary text-white border-primary' : 'bg-base-lvl-2 text-body-1/70 border-base'"
                    @click="toggleTarget(di)">{{ dd }}</button>
          </div>
          <div class="flex items-center gap-3 mb-2 text-xs text-body-1/70">
            <label class="flex items-center gap-1 cursor-pointer">
              <input type="radio" value="replace" v-model="copyMode" />{{ $t('Replace') }}
            </label>
            <label class="flex items-center gap-1 cursor-pointer">
              <input type="radio" value="append" v-model="copyMode" />{{ $t('Add') }}
            </label>
          </div>
          <button type="button"
                  class="w-full text-sm font-semibold bg-primary text-white py-2 rounded-lg disabled:opacity-40 mb-4"
                  :disabled="!copyTargets.length || busyDay"
                  @click="applyDay(dayMenu)">{{ $t('Apply') }}</button>

          <p class="text-[10px] uppercase tracking-wide text-body-1/50 mb-1.5">{{ $t('Assign whole day to') }}</p>
          <div class="flex flex-col gap-0.5 mb-1">
            <button type="button" class="text-left text-sm px-2 py-1.5 rounded-lg hover:bg-base-lvl-2 text-body-1/80"
                    @click="assignDay(dayMenu, null)">{{ $t('Everyone') }}</button>
            <button v-for="m in members" :key="m.id" type="button"
                    class="flex items-center gap-2 text-left text-sm px-2 py-1.5 rounded-lg hover:bg-base-lvl-2 text-body"
                    @click="assignDay(dayMenu, m.id)">
              <span class="w-2.5 h-2.5 rounded-full" :style="{ background: memberColor(m.id) }"></span>{{ m.name }}
            </button>
          </div>

          <div class="flex justify-end pt-2">
            <button type="button" class="text-sm text-body-1 px-3 py-1.5 rounded-lg hover:bg-base-lvl-2" @click="dayMenu = null">{{ $t('Close') }}</button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- undo toast -->
    <Teleport to="body">
      <div v-if="toast" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 bg-base-lvl-3 border border-base rounded-full shadow-2xl px-4 py-2">
        <span class="text-sm text-body">{{ $t('Block deleted') }}</span>
        <button type="button" class="text-sm font-semibold text-primary hover:underline" @click="undoDelete">{{ $t('Undo') }}</button>
      </div>
    </Teleport>

    <!-- modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-black/50" @click.self="showModal = false">
        <div class="w-full max-w-sm bg-base-lvl-3 border border-base rounded-2xl shadow-2xl p-4">
          <h3 class="font-bold text-body mb-3">{{ isEdit ? $t('Edit block') : $t('New block') }}</h3>

          <input v-model="form.title" type="text" :placeholder="$t('Title')"
                 class="w-full mb-2 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none focus:border-primary" />

          <div class="flex gap-2 mb-2">
            <div class="flex-1">
              <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Day') }}</label>
              <select v-model.number="form.day" class="w-full px-2 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none">
                <option v-for="(d, i) in SHORT" :key="i" :value="i">{{ d }}</option>
              </select>
            </div>
            <div>
              <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Start') }}</label>
              <input v-model="form.start" type="time" class="px-2 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none" />
            </div>
            <div>
              <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('End') }}</label>
              <input v-model="form.end" type="time" class="px-2 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none" />
            </div>
          </div>

          <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Member') }}</label>
          <select v-model="form.member_id" class="w-full mb-2 px-2 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none">
            <option :value="null">{{ $t('Everyone') }}</option>
            <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>

          <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Note') }}</label>
          <textarea v-model="form.note" rows="2" :placeholder="$t('Optional details')"
                    class="w-full mb-2 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none focus:border-primary resize-none"></textarea>

          <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Color') }}</label>
          <div class="flex flex-wrap gap-2 mb-4">
            <button v-for="c in COLORS" :key="c" type="button" class="w-6 h-6 rounded-full border-2 transition"
                    :style="{ background: c, borderColor: form.color === c ? '#E7EAF0' : 'transparent' }"
                    @click="form.color = c"></button>
          </div>

          <div class="flex items-center gap-2">
            <button v-if="isEdit" type="button" class="text-sm text-error px-3 py-2 rounded-lg hover:bg-error/10" @click="remove">{{ $t('Delete') }}</button>
            <button v-if="isEdit" type="button" class="text-sm text-body-1 px-3 py-2 rounded-lg hover:bg-base-lvl-2" @click="duplicate">{{ $t('Duplicate') }}</button>
            <button type="button" class="text-sm text-body-1 px-3 py-2 rounded-lg hover:bg-base-lvl-2 ml-auto" @click="showModal = false">{{ $t('Cancel') }}</button>
            <button type="button" class="text-sm font-semibold bg-primary text-white px-4 py-2 rounded-lg disabled:opacity-50"
                    :disabled="!form.title.trim() || saving" @click="save">{{ $t('Save') }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
