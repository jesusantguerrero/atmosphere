<script setup lang="ts">
import { ref, computed, onBeforeUnmount } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import AppLayout from "@/Components/templates/AppLayout.vue";
import HouseSectionNav from "@/Components/templates/HouseSectionNav.vue";
import { Link } from "@inertiajs/vue3";

interface Block {
  id: number;
  day: number;            // 0=Mon .. 6=Sun
  title: string;
  start: string;          // "HH:MM"
  end: string;
  color: string;
  member_id: number | null;
  note: string;
  date: string | null;    // set => dated exception; null => weekly template
  skip: boolean;          // exception that blanks a template slot for that date
}
type RenderBlock = Block & { _kind: "template" | "exception" };
interface Member { id: number; name: string; }
interface PlanPayload { id: number; blocks: Block[]; days: string[]; }

const props = defineProps<{ plan: PlanPayload; members: Member[]; categories?: { color: string; name: string }[] }>();
const { t } = useI18n();

const blocks = ref<Block[]>([...props.plan.blocks]);       // weekly template
const exceptions = ref<Block[]>([]);                       // dated, current week
const SHORT = ["Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"];
const HOUR_H = 40;
const COLORS = ["#56C08A", "#6E9BE6", "#E8A54F", "#E87FA8", "#A98BE0", "#B79B82", "#5CC2C2", "#E0736F", "#6B7686"];

const toMin = (t: string) => {
  const [h, m] = String(t || "0:0").split(":");
  return (parseInt(h) || 0) * 60 + (parseInt(m) || 0);
};
const overlaps = (a: Block, b: Block) => toMin(a.start) < toMin(b.end) && toMin(b.start) < toMin(a.end);

// ---- Week / dates ----
const startOfWeekMon = (d: Date) => {
  const x = new Date(d);
  const wd = (x.getDay() + 6) % 7;                 // 0=Mon
  x.setDate(x.getDate() - wd);
  x.setHours(0, 0, 0, 0);
  return x;
};
const isoOf = (d: Date) =>
  `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;

const viewMode = ref<"template" | "week">("template");
const weekAnchor = ref<Date>(startOfWeekMon(new Date()));
const weekDates = computed(() => Array.from({ length: 7 }, (_, i) => {
  const x = new Date(weekAnchor.value);
  x.setDate(x.getDate() + i);
  return x;
}));
const todayIso = isoOf(new Date());
const weekLabel = computed(() => {
  const a = weekDates.value[0], b = weekDates.value[6];
  return `${a.getDate()}/${a.getMonth() + 1} – ${b.getDate()}/${b.getMonth() + 1}`;
});

const fetchWeek = async () => {
  const { data } = await axios.get(`/housing/routine/${props.plan.id}/week`, {
    params: { date: isoOf(weekDates.value[0]) },
  });
  exceptions.value = data.blocks ?? [];
  fetchClashes();                                    // refresh calendar clashes for this week (non-blocking)
};
const setMode = (m: "template" | "week") => { viewMode.value = m; if (m === "week") fetchWeek(); };
const shiftWeek = (delta: number) => {
  const x = new Date(weekAnchor.value); x.setDate(x.getDate() + delta * 7);
  weekAnchor.value = startOfWeekMon(x); fetchWeek();
};
const goThisWeek = () => { weekAnchor.value = startOfWeekMon(new Date()); fetchWeek(); };

// ---- Google Calendar clash detection (week mode only) ----
// The routine is a guide, not a calendar -- so instead of importing events, we
// just flag the routine blocks that collide with real Google Calendar events
// for the displayed week. Fully optional: if Google isn't connected (or the
// token predates the Calendar scope) we show a hint, never an error.
interface Clash {
  date: string; day: number; block_id: number;
  block_title: string; block_start: string; block_end: string;
  event_title: string; event_start: string; event_end: string;
}
const clashes = ref<Clash[]>([]);
const calConnected = ref(true);
const calError = ref<string | null>(null);

const fetchClashes = async () => {
  if (viewMode.value !== "week") return;
  try {
    const { data } = await axios.get(`/housing/routine/${props.plan.id}/calendar-clashes`, {
      params: { date: isoOf(weekDates.value[0]) },
    });
    clashes.value = data.clashes ?? [];
    calConnected.value = data.connected ?? false;
    calError.value = data.error ?? null;
  } catch {
    clashes.value = []; calConnected.value = false; calError.value = "fetch";
  }
};

const clashByKey = computed(() => {
  const m: Record<string, Clash[]> = {};
  for (const c of clashes.value) (m[`${c.date}|${c.block_id}`] ||= []).push(c);
  return m;
});
const clashBlockCount = computed(() => Object.keys(clashByKey.value).length);
const blockClashes = (rb: RenderBlock, day: number): Clash[] => {
  if (viewMode.value !== "week") return [];
  return clashByKey.value[`${isoOf(weekDates.value[day])}|${rb.id}`] || [];
};
const clashTip = (list: Clash[]) =>
  `${t('Clashes with calendar')}: ` + list.map((c) => `${c.event_title} ${c.event_start}–${c.event_end}`).join(", ");

// Time range auto-expands to fit template + exceptions (early risers / night owls).
const DEFAULT_START = 5;
const DEFAULT_END = 23;
const allForRange = computed(() => [...blocks.value, ...exceptions.value]);
const startH = computed(() => {
  let min = DEFAULT_START;
  for (const b of allForRange.value) min = Math.min(min, Math.floor(toMin(b.start) / 60));
  return Math.max(0, min);
});
const endH = computed(() => {
  let max = DEFAULT_END;
  for (const b of allForRange.value) max = Math.max(max, Math.ceil(toMin(b.end) / 60));
  return Math.min(24, max);
});
const hours = computed(() => Array.from({ length: endH.value - startH.value }, (_, i) => startH.value + i));
const gridHeight = computed(() => hours.value.length * HOUR_H);

// Member filter
const filterMember = ref<number | null>(null);
const filterColor = ref<string | null>(null);   // click a legend swatch to spotlight that category
const toggleColorFilter = (color: string) => { filterColor.value = filterColor.value === color ? null : color; };
const shown = (b: Block) =>
  (filterMember.value === null || b.member_id === filterMember.value) &&
  (filterColor.value === null || b.color === filterColor.value);
const memberName = (id: number | null) => props.members.find((m) => m.id === id)?.name ?? "";
const memberColor = (id: number | null) => {
  if (id === null) return "#69727F";
  const idx = props.members.findIndex((m) => m.id === id);
  return COLORS[(idx + 3) % COLORS.length];
};
const initial = (id: number | null) => (memberName(id) || "?").slice(0, 1).toUpperCase();

// ---- Named categories + time-budget analytics ----
// Colors already act as categories visually; naming them gives a legend,
// accessibility (not color-only) and the weekly time budget.
const catNames = ref<Record<string, string>>({});
(props.categories || []).forEach((c) => { catNames.value[c.color] = c.name; });
const showBudget = ref(false);
const savingCats = ref(false);

const timeBudget = computed(() => {
  const mins: Record<string, number> = {};
  for (const b of blocks.value) {
    const d = toMin(b.end) - toMin(b.start);
    if (d > 0) mins[b.color] = (mins[b.color] || 0) + d;
  }
  const total = Object.values(mins).reduce((a, c) => a + c, 0) || 1;
  return Object.entries(mins)
    .map(([color, m]) => ({ color, minutes: m, hours: Math.round((m / 60) * 10) / 10, pct: Math.round((m / total) * 100) }))
    .sort((a, b) => b.minutes - a.minutes);
});
const budgetTotalHours = computed(() =>
  Math.round((timeBudget.value.reduce((a, c) => a + c.minutes, 0) / 60) * 10) / 10);
const catLabel = (color: string) => (catNames.value[color] || "").trim();

const saveCategories = async () => {
  if (savingCats.value) return;
  savingCats.value = true;
  const payload = timeBudget.value.map((r) => ({ color: r.color, name: catNames.value[r.color] || "" }));
  try { await axios.put(`/housing/routine/${props.plan.id}/categories`, { categories: payload }); }
  finally { savingCats.value = false; }
};

const templateByDay = (day: number) =>
  blocks.value.filter((b) => b.day === day).sort((a, b) => toMin(a.start) - toMin(b.start));

// Effective blocks per column: template as-is, or (week) template minus the
// slots an exception overlaps, plus the visible exceptions (skip = invisible hole).
const renderBlocks = (day: number): RenderBlock[] => {
  if (viewMode.value === "template") {
    return templateByDay(day).map((b) => ({ ...b, _kind: "template" as const }));
  }
  const iso = isoOf(weekDates.value[day]);
  const exc = exceptions.value.filter((e) => e.date === iso);
  const solid = exc.filter((e) => !e.skip);
  const tmpl = templateByDay(day)
    .filter((t) => !exc.some((e) => overlaps(t, e)))
    .map((b) => ({ ...b, _kind: "template" as const }));
  return [...tmpl, ...solid.map((e) => ({ ...e, _kind: "exception" as const }))]
    .sort((a, b) => toMin(a.start) - toMin(b.start));
};

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

// Now-line
const now = new Date();
const nowDow = (now.getDay() + 6) % 7;
const selectedMobileDay = ref(nowDow);   // mobile agenda: which day to show
const nowTop = computed(() => ((now.getHours() * 60 + now.getMinutes() - startH.value * 60) / 60) * HOUR_H);
const nowVisible = computed(() => now.getHours() >= startH.value && now.getHours() < endH.value);
const showNow = (day: number) =>
  nowVisible.value && (viewMode.value === "template" ? day === nowDow : isoOf(weekDates.value[day]) === todayIso);
const isTodayCol = (day: number) => viewMode.value === "week" && isoOf(weekDates.value[day]) === todayIso;

// ---- Modal ----
const showModal = ref(false);
const saving = ref(false);
const blankForm = (day = 0): Block =>
  ({ id: 0, day, title: "", start: "09:00", end: "10:00", color: COLORS[1], member_id: null, note: "", date: null, skip: false });
const form = ref<Block>(blankForm());
const isEdit = computed(() => form.value.id > 0);
const isException = computed(() => !!form.value.date);

const openNew = (day = 0) => {
  const f = blankForm(day);
  if (viewMode.value === "week") f.date = isoOf(weekDates.value[day]);
  form.value = f;
  showModal.value = true;
};
const openEdit = (b: Block) => { form.value = { ...b }; showModal.value = true; };
const duplicate = () => { form.value = { ...form.value, id: 0 }; };

const payloadOf = (b: Block) => ({
  day: b.day, title: b.title.trim(), start: b.start, end: b.end,
  color: b.color, member_id: b.member_id, note: b.note ?? "",
  date: b.date ?? null, skip: b.skip ?? false,
});

const save = async () => {
  if (!form.value.title.trim() || saving.value) return;
  saving.value = true;
  const wasException = isException.value;
  try {
    if (isEdit.value) {
      const { data } = await axios.put(`/housing/routine/${props.plan.id}/blocks/${form.value.id}`, payloadOf(form.value));
      if (wasException) { await fetchWeek(); }
      else { const i = blocks.value.findIndex((b) => b.id === data.id); if (i !== -1) blocks.value[i] = data; }
    } else {
      const { data } = await axios.post(`/housing/routine/${props.plan.id}/blocks`, payloadOf(form.value));
      if (wasException) { await fetchWeek(); }
      else { blocks.value.push(data); }
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
  showModal.value = false;
  await axios.delete(`/housing/routine/${props.plan.id}/blocks/${removed.id}`).catch(() => {});
  if (removed.date) { await fetchWeek(); }
  else { blocks.value = blocks.value.filter((b) => b.id !== removed.id); }
  clearToast();
  toast.value = { block: removed };
  toastTimer = setTimeout(clearToast, 5000);
};

const undoDelete = async () => {
  if (!toast.value) return;
  const b = toast.value.block;
  clearToast();
  const { data } = await axios.post(`/housing/routine/${props.plan.id}/blocks`, payloadOf(b));
  if (b.date) { await fetchWeek(); } else { blocks.value.push(data); }
};

// ---- Quick member assign (per block, floating) ----
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
  if (b.date) { await fetchWeek(); }
  else { const i = blocks.value.findIndex((x) => x.id === data.id); if (i !== -1) blocks.value[i] = data; }
};

// ---- Template-block action menu (week mode) ----
const tmplMenu = ref<{ x: number; y: number; block: Block; date: string } | null>(null);
const openTmplMenu = (b: Block, date: string, ev: MouseEvent) => {
  const w = 190, h = 150;
  const x = Math.max(8, Math.min(ev.clientX, window.innerWidth - w));
  const y = Math.min(ev.clientY, window.innerHeight - h - 8);
  tmplMenu.value = { x, y, block: b, date };
};
const replaceToday = () => {
  if (!tmplMenu.value) return;
  const { block, date } = tmplMenu.value;
  tmplMenu.value = null;
  form.value = { ...block, id: 0, date, skip: false };   // new dated copy to retime
  showModal.value = true;
};
const removeToday = async () => {
  if (!tmplMenu.value) return;
  const { block, date } = tmplMenu.value;
  tmplMenu.value = null;
  await axios.post(`/housing/routine/${props.plan.id}/blocks`, {
    ...payloadOf(block), date, skip: true,
  });
  await fetchWeek();
};
const editTemplate = () => {
  if (!tmplMenu.value) return;
  const b = tmplMenu.value.block;
  tmplMenu.value = null;
  openEdit(b);
};

const onBlockClick = (rb: RenderBlock, day: number, ev: MouseEvent) => {
  if (viewMode.value === "template" || rb._kind === "exception") { openEdit(rb); return; }
  openTmplMenu(rb, isoOf(weekDates.value[day]), ev);
};

// ---- Per-day dialog: duplicate day / assign day (template mode) ----
const dayMenu = ref<number | null>(null);
const copyTargets = ref<number[]>([]);
const copyMode = ref<"replace" | "append">("replace");
const busyDay = ref(false);

const openDay = (day: number) => { dayMenu.value = day; copyTargets.value = []; copyMode.value = "replace"; };
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
  } finally { busyDay.value = false; }
};
const assignDay = async (day: number, memberId: number | null) => {
  if (busyDay.value) return;
  busyDay.value = true;
  try {
    await axios.post(`/housing/routine/${props.plan.id}/assign-day`, { day, member_id: memberId });
    blocks.value = blocks.value.map((b) => (b.day === day ? { ...b, member_id: memberId } : b));
    dayMenu.value = null;
  } finally { busyDay.value = false; }
};

// ---- Drag on the grid: create / move / resize (snap to 15 min) ----
const gridBodyRef = ref<HTMLElement | null>(null);
const SNAP = 15;
const snapMin = (m: number) => Math.max(0, Math.round(m / SNAP) * SNAP);
const minToHHMM = (m: number) => {
  const v = Math.max(0, Math.min(24 * 60, m));
  return `${String(Math.floor(v / 60)).padStart(2, "0")}:${String(v % 60).padStart(2, "0")}`;
};
const drag = ref<any>(null);
const dragMoved = ref(false);

const colGeom = () => {
  const el = gridBodyRef.value;
  if (!el) return null;
  const r = el.getBoundingClientRect();
  return { r, colW: (r.width - 44) / 7 };
};
const yToMin = (clientY: number) => {
  const g = colGeom(); if (!g) return startH.value * 60;
  return startH.value * 60 + ((clientY - g.r.top) / HOUR_H) * 60;
};
const xToDay = (clientX: number) => {
  const g = colGeom(); if (!g) return 0;
  return Math.max(0, Math.min(6, Math.floor((clientX - g.r.left - 44) / g.colW)));
};

const attachDrag = () => { window.addEventListener("pointermove", onDragMove); window.addEventListener("pointerup", onDragUp); };
const detachDrag = () => { window.removeEventListener("pointermove", onDragMove); window.removeEventListener("pointerup", onDragUp); };

const startCreate = (day: number, e: PointerEvent) => {
  const m = snapMin(yToMin(e.clientY));
  drag.value = { mode: "create", day, downX: e.clientX, downY: e.clientY, curStart: m, curEnd: m, curDay: day };
  dragMoved.value = false; attachDrag();
};
const startMove = (rb: RenderBlock, day: number, e: PointerEvent) => {
  drag.value = { mode: "move", block: rb, day, downX: e.clientX, downY: e.clientY, downEvent: e,
    grab: yToMin(e.clientY) - toMin(rb.start), dur: toMin(rb.end) - toMin(rb.start),
    curStart: toMin(rb.start), curEnd: toMin(rb.end), curDay: day };
  dragMoved.value = false; attachDrag();
};
const startResize = (rb: RenderBlock, day: number, e: PointerEvent) => {
  drag.value = { mode: "resize", block: rb, day, downX: e.clientX, downY: e.clientY,
    curStart: toMin(rb.start), curEnd: toMin(rb.end), curDay: day };
  dragMoved.value = false; attachDrag();
};

const onDragMove = (e: PointerEvent) => {
  const d = drag.value; if (!d) return;
  if (!dragMoved.value) {
    if (Math.abs(e.clientX - d.downX) < 4 && Math.abs(e.clientY - d.downY) < 4) return;
    dragMoved.value = true;
  }
  if (d.mode === "create") { d.curEnd = snapMin(yToMin(e.clientY)); }
  else if (d.mode === "move") { const ns = snapMin(yToMin(e.clientY) - d.grab); d.curStart = ns; d.curEnd = ns + d.dur; d.curDay = xToDay(e.clientX); }
  else if (d.mode === "resize") { d.curEnd = Math.max(d.curStart + SNAP, snapMin(yToMin(e.clientY))); }
};

const persistBlockChange = async (orig: RenderBlock, next: any) => {
  const { data } = await axios.put(`/housing/routine/${props.plan.id}/blocks/${orig.id}`, payloadOf(next));
  if (orig.date) { await fetchWeek(); }
  else { const i = blocks.value.findIndex((b) => b.id === (data?.id ?? orig.id)); if (i !== -1) blocks.value[i] = data; }
};

const onDragUp = async (e: PointerEvent) => {
  detachDrag();
  const d = drag.value; drag.value = null;
  if (!d) return;
  const moved = dragMoved.value;
  if (d.mode === "create") {
    if (!moved) return;                                   // a plain click, not a drag
    const a = Math.min(d.curStart, d.curEnd), b = Math.max(d.curStart, d.curEnd);
    if (b - a < SNAP) return;
    const fm = blankForm(d.day);
    fm.start = minToHHMM(a); fm.end = minToHHMM(b);
    if (viewMode.value === "week") fm.date = isoOf(weekDates.value[d.day]);
    form.value = fm; showModal.value = true;
    return;
  }
  if (!moved) { if (d.block) onBlockClick(d.block, d.day, d.downEvent ?? e); return; }  // tap = edit
  if (d.mode === "move") await persistBlockChange(d.block, { ...d.block, day: d.curDay, start: minToHHMM(d.curStart), end: minToHHMM(d.curEnd) });
  else if (d.mode === "resize") await persistBlockChange(d.block, { ...d.block, end: minToHHMM(d.curEnd) });
};

const dragGhost = computed(() => {
  const d = drag.value; if (!d || !dragMoved.value) return null;
  const a = Math.min(d.curStart, d.curEnd), b = Math.max(d.curStart, d.curEnd);
  return { day: d.curDay ?? d.day, top: ((a - startH.value * 60) / 60) * HOUR_H, height: Math.max(6, ((b - a) / 60) * HOUR_H), label: minToHHMM(a) + "–" + minToHHMM(b) };
});

onBeforeUnmount(() => { if (toastTimer) clearTimeout(toastTimer); detachDrag(); });
</script>

<template>
  <AppLayout :title="$t('Routine')">
    <template #header>
      <HouseSectionNav />
    </template>

    <div class="px-5 pb-20 pt-16 w-full">
      <!-- header: mode toggle + week nav + filter + add -->
      <div class="flex items-center gap-2 mb-2 flex-wrap">
        <div class="flex items-center gap-3 mr-auto">
          <h1 class="text-lg font-bold text-body">{{ $t('Weekly routine') }}</h1>
          <Link
              href="/calendar"
              class="text-xs font-semibold px-2.5 py-1 rounded-full border border-base bg-base-lvl-1 text-body-1/70 hover:text-body hover:border-primary/40 transition inline-flex items-center gap-1.5"
              :title="$t('Calendar')"
          >
              <i class="fa fa-calendar-days"></i>
              {{ $t('Calendar') }}
          </Link>
        </div>
        <div class="flex rounded-lg bg-base-lvl-1 border border-base p-0.5">
          <button class="text-xs font-semibold px-3 py-1 rounded-md transition"
                  :class="viewMode === 'template' ? 'bg-base-lvl-3 text-body' : 'text-body-1/60'"
                  @click="setMode('template')">{{ $t('Template') }}</button>
          <button class="text-xs font-semibold px-3 py-1 rounded-md transition"
                  :class="viewMode === 'week' ? 'bg-base-lvl-3 text-body' : 'text-body-1/60'"
                  @click="setMode('week')">{{ $t('This week') }}</button>
        </div>
        <button class="hidden md:inline-flex text-xs font-semibold px-3 py-1.5 rounded-lg border transition"
                :class="showBudget ? 'bg-base-lvl-3 text-body border-base-lvl-1' : 'bg-base-lvl-1 text-body-1/70 border-base'"
                @click="showBudget = !showBudget">📊 {{ $t('Time budget') }}</button>
      </div>

      <div class="flex items-center gap-2 mb-4 flex-wrap">
        <!-- week nav -->
        <div v-if="viewMode === 'week'" class="flex items-center gap-1 mr-2">
          <button class="w-7 h-7 rounded-lg bg-base-lvl-1 border border-base text-body-1/70 hover:text-body" @click="shiftWeek(-1)">‹</button>
          <span class="text-xs font-semibold text-body px-1 min-w-[92px] text-center">{{ weekLabel }}</span>
          <button class="w-7 h-7 rounded-lg bg-base-lvl-1 border border-base text-body-1/70 hover:text-body" @click="shiftWeek(1)">›</button>
          <button class="text-[11px] font-semibold px-2 py-1 rounded-lg bg-base-lvl-1 border border-base text-body-1/70 hover:text-body" @click="goThisWeek">{{ $t('Today') }}</button>
        </div>

        <button
          class="text-xs font-semibold px-3 py-1.5 rounded-full border transition ml-auto"
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

      <!-- calendar clash banner (week mode): hint to connect, or a clash count -->
      <div v-if="viewMode === 'week'" class="mb-3">
        <div v-if="!calConnected"
             class="flex items-center gap-2 text-xs px-3 py-2 rounded-lg bg-base-lvl-1 border border-base text-body-1/70">
          <span>📅</span>
          <span>{{ calError === 'reauth' ? $t('Your Google connection expired. Reconnect it to detect clashes.') : $t('Connect Google Calendar to detect clashes with your routine.') }}</span>
          <a href="/integrations" class="ml-auto font-semibold text-primary hover:underline">{{ $t('Connect Google') }}</a>
        </div>
        <div v-else-if="calError === 'scope'"
             class="flex items-center gap-2 text-xs px-3 py-2 rounded-lg bg-base-lvl-1 border border-base text-body-1/70">
          <span>📅</span>
          <span>{{ $t('Reconnect Google to include your calendar and detect clashes.') }}</span>
          <a href="/integrations" class="ml-auto font-semibold text-primary hover:underline">{{ $t('Reconnect') }}</a>
        </div>
        <div v-else-if="calError === 'fetch'"
             class="text-xs px-3 py-2 rounded-lg bg-base-lvl-1 border border-base text-body-1/50">
          {{ $t("Couldn't read your calendar right now.") }}
        </div>
        <div v-else-if="clashBlockCount"
             class="flex items-center gap-2 text-xs px-3 py-2 rounded-lg bg-error/10 border border-error/30 text-error font-semibold">
          <span>⚠</span>
          <span>{{ $t('{n} routine block(s) clash with your calendar this week.', { n: clashBlockCount }) }}</span>
        </div>
        <div v-else
             class="flex items-center gap-2 text-xs px-3 py-2 rounded-lg bg-base-lvl-1 border border-base text-body-1/50">
          <span>✓</span>
          <span>{{ $t('No clashes with your calendar this week.') }}</span>
        </div>
      </div>

      <!-- mobile day agenda: the 7-column grid is unusable on phones -->
      <div class="md:hidden">
        <div class="flex gap-1.5 overflow-x-auto pb-2 mb-2">
          <button v-for="(d, i) in SHORT" :key="i"
                  class="flex-none px-3 py-1.5 rounded-lg text-xs font-semibold border transition"
                  :class="selectedMobileDay === i ? 'bg-primary text-white border-primary' : 'bg-base-lvl-1 text-body-1/70 border-base'"
                  @click="selectedMobileDay = i">
            {{ d }}<span v-if="viewMode === 'week'" class="ml-1 opacity-80">{{ weekDates[i].getDate() }}</span>
          </button>
        </div>
        <div class="space-y-2">
          <div v-for="rb in renderBlocks(selectedMobileDay)" :key="rb._kind + rb.id"
               class="flex items-stretch gap-3 rounded-lg bg-base-lvl-3 border border-base p-3 cursor-pointer"
               :class="{ 'opacity-40': !shown(rb) }"
               @click="openEdit(rb)">
            <div class="w-1 rounded-full flex-none" :style="{ background: rb.color }"></div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-semibold text-body truncate flex items-center gap-1">
                {{ rb.title }}
                <span v-if="blockClashes(rb, selectedMobileDay).length" class="text-[11px] text-error">⚠</span>
                <span v-if="rb._kind === 'exception'" class="text-[10px] text-primary">★</span>
              </div>
              <div class="text-xs text-body-1/60">{{ rb.start }}–{{ rb.end }}</div>
              <div v-if="blockClashes(rb, selectedMobileDay).length" class="text-[11px] text-error mt-0.5 truncate">⚠ {{ clashTip(blockClashes(rb, selectedMobileDay)) }}</div>
              <div v-else-if="rb.note" class="text-[11px] text-body-1/50 mt-0.5 truncate">{{ rb.note }}</div>
            </div>
            <span v-if="rb.member_id" class="w-6 h-6 rounded-full text-[10px] flex items-center justify-center font-bold text-white flex-none self-center"
                  :style="{ background: memberColor(rb.member_id) }">{{ initial(rb.member_id) }}</span>
          </div>
          <p v-if="!renderBlocks(selectedMobileDay).length" class="text-center text-xs text-body-1/50 py-8">{{ $t('No blocks this day') }}</p>
        </div>
        <button class="w-full mt-3 text-sm font-semibold px-3 py-2 rounded-lg bg-primary text-white" @click="openNew(selectedMobileDay)">+ {{ $t('Block') }}</button>
      </div>

      <!-- grid + side budget panel: the panel NARROWS the grid (which scrolls
           horizontally), so it never pushes the grid down nor covers it. -->
      <div class="hidden md:flex gap-4 items-start">
        <div class="flex-1 min-w-0">
        <div class="bg-base-lvl-3 border border-base rounded-xl overflow-hidden">
        <div class="grid border-b border-base bg-base-lvl-2" style="grid-template-columns:44px repeat(7,1fr)">
          <div></div>
          <div v-for="(d, i) in SHORT" :key="i" class="relative py-2 text-center text-[11px] font-bold"
               :class="isTodayCol(i) ? 'text-primary' : ((viewMode === 'template' && i === nowDow) ? 'text-body' : 'text-body-1/60')">
            {{ d }}<span v-if="viewMode === 'week'" class="ml-1 opacity-70">{{ weekDates[i].getDate() }}</span>
            <button
              v-if="viewMode === 'template'"
              class="absolute right-1 top-1.5 w-4 h-4 rounded text-body-1/40 hover:text-body hover:bg-base-lvl-1 leading-none"
              @click.stop="openDay(i)"
              :title="$t('Day options')"
            >⋯</button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <div ref="gridBodyRef" class="grid relative" style="grid-template-columns:44px repeat(7,1fr);min-width:760px">
            <!-- time gutter -->
            <div class="relative" :style="{ height: gridHeight + 'px' }">
              <div v-for="h in hours" :key="h" class="absolute right-1.5 text-[9px] text-body-1/40"
                   :style="{ top: ((h - startH) * HOUR_H - 5) + 'px' }">{{ (h < 10 ? '0' : '') + h }}:00</div>
            </div>
            <!-- day columns -->
            <div v-for="day in 7" :key="day" class="relative border-l border-base/50 select-none"
                 :class="isTodayCol(day - 1) ? 'bg-primary/5' : ''"
                 :style="{ height: gridHeight + 'px' }"
                 @pointerdown.self="startCreate(day - 1, $event)"
                 @dblclick="openNew(day - 1)">
              <div v-for="h in hours" :key="h" class="absolute left-0 right-0 border-t border-base/30"
                   :style="{ top: ((h - startH) * HOUR_H) + 'px' }"></div>
              <div v-if="showNow(day - 1)" class="absolute left-0 right-0 h-0.5 bg-primary z-10"
                   :style="{ top: nowTop + 'px' }"></div>
              <div v-if="dragGhost && dragGhost.day === (day - 1)"
                   class="absolute left-0.5 right-0.5 rounded-md border-2 border-dashed border-primary bg-primary/10 pointer-events-none z-20 flex items-start justify-center text-[9px] text-primary font-bold pt-0.5"
                   :style="{ top: dragGhost.top + 'px', height: dragGhost.height + 'px' }">{{ dragGhost.label }}</div>
              <!-- blocks -->
              <div v-for="rb in renderBlocks(day - 1)" :key="rb._kind + rb.id"
                   class="absolute left-0.5 right-0.5 rounded-md px-1.5 py-0.5 text-[10px] font-semibold overflow-hidden cursor-pointer leading-tight transition"
                   :class="[
                     !shown(rb) ? 'opacity-20 grayscale' : '',
                     blockClashes(rb, day - 1).length ? 'ring-2 ring-error' : (rb._kind === 'exception' ? 'ring-1 ring-current' : (viewMode === 'week' ? 'opacity-60 border-dashed' : '')),
                   ]"
                   :style="blockStyle(rb)"
                   :title="blockClashes(rb, day - 1).length ? clashTip(blockClashes(rb, day - 1)) : (rb.note || rb.title)"
                   @pointerdown.stop="startMove(rb, day - 1, $event)">
                <div class="truncate flex items-center gap-1">
                  {{ rb.title }}
                  <span v-if="blockClashes(rb, day - 1).length" class="text-[8px] text-error">⚠</span>
                  <span v-if="rb._kind === 'exception'" class="text-[8px]">★</span>
                  <span v-else-if="rb.note" class="opacity-60 text-[8px]">✎</span>
                </div>
                <div class="text-[8px] opacity-70">{{ rb.start }}–{{ rb.end }}</div>
                <button
                  class="absolute top-0.5 right-0.5 w-3.5 h-3.5 rounded-full text-[7px] flex items-center justify-center font-bold text-white ring-1 ring-white/30"
                  :style="{ background: memberColor(rb.member_id) }"
                  @pointerdown.stop
                  @click.stop="openAssign(rb, $event)"
                  :title="$t('Assign member')"
                >{{ rb.member_id ? initial(rb.member_id) : '+' }}</button>
                <div class="absolute left-0 right-0 bottom-0 h-1.5 cursor-ns-resize hover:bg-primary/40" @pointerdown.stop="startResize(rb, day - 1, $event)"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p class="text-[11px] text-body-1/50 mt-3">
        <span v-if="viewMode === 'template'">{{ $t('Double-tap a day to add · tap a block to edit · ⋯ to duplicate a day. This template repeats every week.') }}</span>
        <span v-else>{{ $t('This week only: exceptions (★) override the template for that date. Tap a faded template block to replace or remove it just for today.') }}</span>
      </p>
        </div>

        <!-- side budget panel (in-layout, narrows the grid instead of covering it) -->
        <div v-if="showBudget" class="w-80 flex-none bg-base-lvl-3 border border-base rounded-xl p-4 sticky top-20 max-h-[calc(100vh-7rem)] overflow-y-auto">
          <div class="flex items-center mb-1">
            <h2 class="text-base font-bold text-body">{{ $t('Weekly time budget') }}</h2>
            <button class="ml-auto text-body-1/60 hover:text-body text-lg leading-none" @click="showBudget = false">✕</button>
          </div>
          <p class="text-xs text-body-1/60 mb-4">{{ budgetTotalHours }} {{ $t('h/week') }}</p>
          <div class="flex h-3 w-full rounded-full overflow-hidden mb-4">
            <div v-for="c in timeBudget" :key="c.color" class="h-full" :style="{ width: c.pct + '%', background: c.color }"
                 :title="(catLabel(c.color) || $t('Unnamed')) + ' · ' + c.hours + 'h'"></div>
          </div>
          <div class="space-y-1.5">
            <div v-for="c in timeBudget" :key="c.color" class="flex items-center gap-2 transition"
                 :class="filterColor && filterColor !== c.color ? 'opacity-40' : ''">
              <button type="button" class="w-4 h-4 rounded-sm flex-none transition ring-offset-2 ring-offset-base-lvl-3"
                      :class="filterColor === c.color ? 'ring-2 ring-white' : 'hover:scale-125'"
                      :style="{ background: c.color }" @click="toggleColorFilter(c.color)"
                      :title="$t('Filter by this category')"></button>
              <input v-model="catNames[c.color]" type="text" :placeholder="$t('Name this category')"
                     class="flex-1 min-w-0 px-2 py-1 text-xs rounded bg-base-lvl-2 border border-base text-body outline-none focus:border-primary" />
              <span class="text-xs font-semibold text-body tabular-nums w-12 text-right">{{ c.hours }}h</span>
              <span class="text-[10px] text-body-1/50 tabular-nums w-8 text-right">{{ c.pct }}%</span>
            </div>
          </div>
          <button class="w-full mt-4 text-xs font-semibold px-3 py-2 rounded-lg bg-primary text-white disabled:opacity-50"
                  :disabled="savingCats" @click="saveCategories">{{ $t('Save categories') }}</button>
          <p class="text-[11px] text-body-1/50 mt-3">{{ $t('Name each color once and it becomes a category across the routine. The budget uses the weekly template.') }}</p>
        </div>
      </div>
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

    <!-- template-block action menu (week mode) -->
    <Teleport to="body">
      <div v-if="tmplMenu" class="fixed inset-0 z-40" @click="tmplMenu = null">
        <div class="absolute w-48 bg-base-lvl-3 border border-base rounded-lg shadow-2xl p-1 text-body"
             :style="{ left: tmplMenu.x + 'px', top: (tmplMenu.y + 6) + 'px' }" @click.stop>
          <button type="button" class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-base-lvl-2" @click="replaceToday">{{ $t('Replace just today') }}</button>
          <button type="button" class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-base-lvl-2" @click="removeToday">{{ $t('Remove just today') }}</button>
          <div class="border-t border-base my-1"></div>
          <button type="button" class="w-full text-left text-xs px-2 py-1.5 rounded hover:bg-base-lvl-2 text-body-1/70" @click="editTemplate">{{ $t('Edit template') }}</button>
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
            <button v-for="(dd, di) in SHORT" :key="di" v-show="di !== dayMenu" type="button"
                    class="text-xs px-2 py-1 rounded-lg border transition"
                    :class="copyTargets.includes(di) ? 'bg-primary text-white border-primary' : 'bg-base-lvl-2 text-body-1/70 border-base'"
                    @click="toggleTarget(di)">{{ dd }}</button>
          </div>
          <div class="flex items-center gap-3 mb-2 text-xs text-body-1/70">
            <label class="flex items-center gap-1 cursor-pointer"><input type="radio" value="replace" v-model="copyMode" />{{ $t('Replace') }}</label>
            <label class="flex items-center gap-1 cursor-pointer"><input type="radio" value="append" v-model="copyMode" />{{ $t('Add') }}</label>
          </div>
          <button type="button" class="w-full text-sm font-semibold bg-primary text-white py-2 rounded-lg disabled:opacity-40 mb-4"
                  :disabled="!copyTargets.length || busyDay" @click="applyDay(dayMenu)">{{ $t('Apply') }}</button>
          <p class="text-[10px] uppercase tracking-wide text-body-1/50 mb-1.5">{{ $t('Assign whole day to') }}</p>
          <div class="flex flex-col gap-0.5 mb-1">
            <button type="button" class="text-left text-sm px-2 py-1.5 rounded-lg hover:bg-base-lvl-2 text-body-1/80" @click="assignDay(dayMenu, null)">{{ $t('Everyone') }}</button>
            <button v-for="m in members" :key="m.id" type="button"
                    class="flex items-center gap-2 text-left text-sm px-2 py-1.5 rounded-lg hover:bg-base-lvl-2 text-body" @click="assignDay(dayMenu, m.id)">
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
          <h3 class="font-bold text-body mb-1">{{ isEdit ? $t('Edit block') : $t('New block') }}</h3>
          <p v-if="isException" class="text-[11px] text-primary font-semibold mb-3">★ {{ $t('Exception — this date only') }}: {{ form.date }}</p>
          <div v-else class="mb-3"></div>

          <input v-model="form.title" type="text" :placeholder="$t('Title')"
                 class="w-full mb-2 px-3 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none focus:border-primary" />

          <div class="flex gap-2 mb-2">
            <div class="flex-1">
              <label class="block text-[9px] uppercase tracking-wide text-body-1/50 mb-1">{{ $t('Day') }}</label>
              <select v-model.number="form.day" :disabled="isException"
                      class="w-full px-2 py-2 text-sm rounded-lg bg-base-lvl-2 border border-base text-body outline-none disabled:opacity-50">
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
