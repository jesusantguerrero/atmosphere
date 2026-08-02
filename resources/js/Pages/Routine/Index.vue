<script setup lang="ts">
import { ref, computed } from "vue";
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
}
interface Member { id: number; name: string; }
interface PlanPayload { id: number; blocks: Block[]; days: string[]; }

const props = defineProps<{ plan: PlanPayload; members: Member[] }>();

const blocks = ref<Block[]>([...props.plan.blocks]);
const SHORT = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
const START_H = 5;
const END_H = 23;
const HOUR_H = 40;
const COLORS = ["#56C08A", "#6E9BE6", "#E8A54F", "#E87FA8", "#A98BE0", "#B79B82", "#5CC2C2", "#E0736F", "#6B7686"];

const toMin = (t: string) => {
  const [h, m] = String(t || "0:0").split(":");
  return (parseInt(h) || 0) * 60 + (parseInt(m) || 0);
};
const hours = computed(() => Array.from({ length: END_H - START_H }, (_, i) => START_H + i));

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
  const top = ((toMin(b.start) - START_H * 60) / 60) * HOUR_H;
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
const nowTop = ((now.getHours() * 60 + now.getMinutes() - START_H * 60) / 60) * HOUR_H;
const nowVisible = now.getHours() >= START_H && now.getHours() < END_H;

// ---- Modal ----
const showModal = ref(false);
const saving = ref(false);
const form = ref<Block>({ id: 0, day: 0, title: "", start: "09:00", end: "10:00", color: COLORS[1], member_id: null });
const isEdit = computed(() => form.value.id > 0);

const openNew = (day = 0) => {
  form.value = { id: 0, day, title: "", start: "09:00", end: "10:00", color: COLORS[1], member_id: null };
  showModal.value = true;
};
const openEdit = (b: Block) => {
  form.value = { ...b };
  showModal.value = true;
};

const save = async () => {
  if (!form.value.title.trim() || saving.value) return;
  saving.value = true;
  const payload = {
    day: form.value.day,
    title: form.value.title.trim(),
    start: form.value.start,
    end: form.value.end,
    color: form.value.color,
    member_id: form.value.member_id,
  };
  try {
    if (isEdit.value) {
      const { data } = await axios.put(`/housing/routine/${props.plan.id}/blocks/${form.value.id}`, payload);
      const i = blocks.value.findIndex((b) => b.id === data.id);
      if (i !== -1) blocks.value[i] = data;
    } else {
      const { data } = await axios.post(`/housing/routine/${props.plan.id}/blocks`, payload);
      blocks.value.push(data);
    }
    showModal.value = false;
  } finally {
    saving.value = false;
  }
};

const remove = async () => {
  if (!isEdit.value) return;
  if (!window.confirm("¿Eliminar este bloque?")) return;
  saving.value = true;
  try {
    await axios.delete(`/housing/routine/${props.plan.id}/blocks/${form.value.id}`);
    blocks.value = blocks.value.filter((b) => b.id !== form.value.id);
    showModal.value = false;
  } finally {
    saving.value = false;
  }
};
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
          <div v-for="(d, i) in SHORT" :key="i" class="py-2 text-center text-[11px] font-bold"
               :class="i === nowDow ? 'text-body' : 'text-body-1/60'">{{ d }}</div>
        </div>
        <div class="overflow-x-auto">
          <div class="grid relative" style="grid-template-columns:44px repeat(7,1fr);min-width:760px">
            <!-- time gutter -->
            <div class="relative" :style="{ height: (hours.length * HOUR_H) + 'px' }">
              <div v-for="h in hours" :key="h" class="absolute right-1.5 text-[9px] text-body-1/40"
                   :style="{ top: ((h - START_H) * HOUR_H - 5) + 'px' }">{{ (h < 10 ? '0' : '') + h }}:00</div>
            </div>
            <!-- day columns -->
            <div v-for="day in 7" :key="day" class="relative border-l border-base/50"
                 :style="{ height: (hours.length * HOUR_H) + 'px' }">
              <!-- hour lines -->
              <div v-for="h in hours" :key="h" class="absolute left-0 right-0 border-t border-base/30"
                   :style="{ top: ((h - START_H) * HOUR_H) + 'px' }"></div>
              <!-- now line -->
              <div v-if="nowVisible && (day - 1) === nowDow" class="absolute left-0 right-0 h-0.5 bg-primary z-10"
                   :style="{ top: nowTop + 'px' }"></div>
              <!-- blocks -->
              <div v-for="b in blocksByDay(day - 1)" :key="b.id"
                   class="absolute left-0.5 right-0.5 rounded-md px-1.5 py-0.5 text-[10px] font-semibold overflow-hidden cursor-pointer leading-tight transition"
                   :class="{ 'opacity-20 grayscale': !shown(b) }"
                   :style="blockStyle(b)"
                   @click="openEdit(b)">
                <div class="truncate">{{ b.title }}</div>
                <div class="text-[8px] opacity-70">{{ b.start }}–{{ b.end }}</div>
                <span v-if="b.member_id" class="absolute top-0.5 right-0.5 w-3 h-3 rounded-full text-[7px] flex items-center justify-center font-bold text-white"
                      :style="{ background: memberColor(b.member_id) }">{{ initial(b.member_id) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p class="text-[11px] text-body-1/50 mt-3">{{ $t('Tap a block to edit, or + Block to add. This template repeats every week.') }}</p>
    </div>

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

          <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Color') }}</label>
          <div class="flex flex-wrap gap-2 mb-4">
            <button v-for="c in COLORS" :key="c" type="button" class="w-6 h-6 rounded-full border-2 transition"
                    :style="{ background: c, borderColor: form.color === c ? '#E7EAF0' : 'transparent' }"
                    @click="form.color = c"></button>
          </div>

          <div class="flex items-center gap-2">
            <button v-if="isEdit" type="button" class="text-sm text-error px-3 py-2 rounded-lg hover:bg-error/10" @click="remove">{{ $t('Delete') }}</button>
            <button type="button" class="text-sm text-body-1 px-3 py-2 rounded-lg hover:bg-base-lvl-2 ml-auto" @click="showModal = false">{{ $t('Cancel') }}</button>
            <button type="button" class="text-sm font-semibold bg-primary text-white px-4 py-2 rounded-lg disabled:opacity-50"
                    :disabled="!form.title.trim() || saving" @click="save">{{ $t('Save') }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
