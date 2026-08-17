<script setup lang="ts">
/**
 * FamilyView — a kitchen-screen friendly board view for chores.
 * Not a replacement: it is registered alongside List/Matrix in the view
 * selector. Groups chores into one lane per family member (from the injected
 * `users` = LogerProfile members), renders each chore as a big card with a big
 * check circle a child can tap, shows a large done-points counter per lane, and
 * offers a "Today" filter. Marking done persists is_done straight to the item
 * endpoint (independent of the list's add/reload plumbing).
 */
import { computed, inject, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useAppContextStore } from '@/store';

const context = useAppContextStore();

const props = defineProps<{
  stages: any[];
  fields?: any[];
  boardId?: number;
  kiosk?: boolean;
  kanbanData?: Record<string, any>;
  resourceName?: string;
}>();

const users = inject<any[]>('users', []);

// Same palette/offset as the Routine (Rutina) chips so a member reads with the
// same colour across the app.
const COLORS = ['#56C08A', '#6E9BE6', '#E8A54F', '#E87FA8', '#A98BE0', '#B79B82', '#5CC2C2', '#E0736F', '#6B7686'];
const memberColor = (idx: number) => COLORS[(idx + 3) % COLORS.length];

const todayLabel = new Date().toLocaleDateString('es', { weekday: 'long', day: 'numeric', month: 'long' });

const allItems = computed(() => (props.stages || []).flatMap((s: any) => s.items || []));

// The chores payload is already scoped to today (pending + completed today) by
// the controller, so every visible done card counts toward today's streak.
const visibleItems = computed(() => allItems.value);

const lanes = computed(() => {
  const list = (users || []).map((m: any, idx: number) => ({
    key: String(m.value ?? m.name ?? idx),
    name: m.name,
    color: memberColor(idx),
    unassigned: false,
    items: visibleItems.value.filter((it: any) => (it.owner ?? '') === (m.value ?? m.name)),
  }));
  const unassigned = visibleItems.value.filter((it: any) => !it.owner);
  if (unassigned.length || !list.length) {
    list.push({ key: '__unassigned__', name: '', color: '#9CA3AF', unassigned: true, items: unassigned });
  }
  return list;
});

const donePoints = (lane: any) => lane.items.filter((i: any) => i.is_done).length;
const laneInitial = (lane: any) => (lane.unassigned ? '?' : (lane.name || '?')).slice(0, 1).toUpperCase();
const lanePct = (lane: any) => (lane.items.length ? Math.round((donePoints(lane) / lane.items.length) * 100) : 0);

const busy = ref<Record<number, boolean>>({});

async function toggleDone(item: any) {
  if (busy.value[item.id]) return;
  busy.value[item.id] = true;
  const next = !item.is_done;
  item.is_done = next; // optimistic
  try {
    await axios.put(`/housing/plans/${props.boardId ?? item.board_id}/items/${item.id}`, { is_done: next });
    router.reload({ preserveScroll: true });
  } catch (e) {
    item.is_done = !next; // revert on failure
  } finally {
    busy.value[item.id] = false;
  }
}

const draft = ref<Record<string, string>>({});
const openAssign = ref<number | null>(null);
const openRec = ref<number | null>(null);
const recurrencePresets = [
  { key: 'daily', label: 'Daily' },
  { key: 'weekdays', label: 'Weekdays' },
  { key: 'weekly', label: 'Weekly' },
  { key: 'once', label: 'Once' },
];

function recurrenceKey(item: any): string {
  const s = (item.rrule || '').toUpperCase();
  if (!s) return 'Once';
  if (s.includes('BYDAY=MO,TU,WE,TH,FR')) return 'Weekdays';
  if (s.includes('FREQ=DAILY')) return 'Daily';
  if (s.includes('FREQ=WEEKLY')) return 'Weekly';
  return 'Daily';
}

async function setRecurrence(item: any, preset: string) {
  openRec.value = null;
  try {
    await axios.put(`/housing/plans/${props.boardId ?? item.board_id}/items/${item.id}`, { recurrence: preset });
    router.reload({ preserveScroll: true });
  } catch (e) { /* ignore */ }
}

async function assign(item: any, value: string | null) {
  openAssign.value = null;
  const ownerField = (props.fields || []).find((f: any) => f.name === 'owner');
  if (!ownerField) return;
  const prev = item.owner;
  item.owner = value ?? ''; // optimistic
  try {
    await axios.put(`/housing/plans/${props.boardId ?? item.board_id}/items/${item.id}`, {
      fields: [{ field_id: ownerField.id, field_name: 'owner', name: 'owner', value: value ?? '' }],
    });
    router.reload({ preserveScroll: true });
  } catch (e) {
    item.owner = prev;
  }
}

async function addChore(lane: any) {
  const title = (draft.value[lane.key] || '').trim();
  if (!title) return;
  const stageId = props.stages?.[0]?.id;
  const ownerField = (props.fields || []).find((f: any) => f.name === 'owner');
  // Adding inside a member's lane pre-assigns them as the chore owner.
  const fields = (!lane.unassigned && ownerField)
    ? [{ field_id: ownerField.id, field_name: 'owner', name: 'owner', value: lane.key }]
    : [];
  draft.value[lane.key] = '';
  try {
    await axios.post(`/housing/plans/${props.boardId}/items`, {
      title,
      stage_id: stageId,
      order: lane.items.length,
      fields,
    });
    router.reload({ preserveScroll: true });
  } catch (e) {
    draft.value[lane.key] = title; // restore so the user doesn't lose input
  }
}

// ---- Mobile flat list (Maple-style) ----------------------------------------
// On phones the per-person lanes stack into long columns; instead we render one
// flat, filterable list of chores. Filters: a status/date segment + a person
// selector. Desktop keeps the lanes untouched.
const memberMeta = computed<Record<string, { name: string; color: string; initial: string }>>(() => {
  const map: Record<string, { name: string; color: string; initial: string }> = {};
  (users || []).forEach((m: any, idx: number) => {
    map[String(m.value ?? m.name)] = {
      name: m.name,
      color: memberColor(idx),
      initial: (m.name || '?').slice(0, 1).toUpperCase(),
    };
  });
  return map;
});
const ownerMeta = (item: any) =>
  memberMeta.value[String(item.owner ?? '')] || { name: '', color: '#9CA3AF', initial: '?' };

const hasUnassigned = computed(() => visibleItems.value.some((it: any) => !it.owner));

// Date/status segment. The chores payload is today-scoped, so the meaningful
// cuts are pending vs. completed vs. all; "overdue" surfaces any dated item
// whose due date is past and still open.
const statusFilter = ref<'all' | 'pending' | 'done' | 'overdue'>('pending');
const personFilter = ref<string | null>(null); // null = everyone; '__unassigned__' = no owner

const todayStr = new Date().toISOString().slice(0, 10);
const isOverdue = (it: any) => !it.is_done && it.due_date && String(it.due_date).slice(0, 10) < todayStr;

const matchesStatus = (it: any) => {
  if (statusFilter.value === 'pending') return !it.is_done;
  if (statusFilter.value === 'done') return !!it.is_done;
  if (statusFilter.value === 'overdue') return isOverdue(it);
  return true;
};
const matchesPerson = (it: any) => {
  if (personFilter.value == null) return true;
  if (personFilter.value === '__unassigned__') return !it.owner;
  return String(it.owner ?? '') === String(personFilter.value);
};

// Person-scoped first (so the status counts reflect the chosen person), then
// status-scoped for the visible list.
const personScoped = computed(() => visibleItems.value.filter(matchesPerson));
const flatItems = computed(() => personScoped.value.filter(matchesStatus));

const statusChips = computed(() => [
  { key: 'pending', label: 'Pending', count: personScoped.value.filter((i: any) => !i.is_done).length },
  { key: 'overdue', label: 'Overdue', count: personScoped.value.filter(isOverdue).length },
  { key: 'done', label: 'Completed', count: personScoped.value.filter((i: any) => i.is_done).length },
  { key: 'all', label: 'All', count: personScoped.value.length },
] as const);

const personCount = (value: any) =>
  visibleItems.value.filter((it: any) => String(it.owner ?? '') === String(value)).length;

// Bottom add input: adds to the selected person (or unassigned when "All").
const flatDraft = ref('');
async function addFlat() {
  const title = (flatDraft.value || '').trim();
  if (!title) return;
  const stageId = props.stages?.[0]?.id;
  const ownerField = (props.fields || []).find((f: any) => f.name === 'owner');
  const targetOwner =
    personFilter.value && personFilter.value !== '__unassigned__' ? personFilter.value : null;
  const fields = targetOwner && ownerField
    ? [{ field_id: ownerField.id, field_name: 'owner', name: 'owner', value: targetOwner }]
    : [];
  flatDraft.value = '';
  try {
    await axios.post(`/housing/plans/${props.boardId}/items`, {
      title,
      stage_id: stageId,
      order: visibleItems.value.length,
      fields,
    });
    router.reload({ preserveScroll: true });
  } catch (e) {
    flatDraft.value = title; // restore on failure
  }
}
</script>

<template>
  <div class="flex flex-col w-full pb-20">
    <div v-if="!kiosk" class="flex flex-wrap items-center justify-end gap-2 mb-5">
      <button
        type="button"
        class="inline-flex items-center gap-2 px-3.5 py-1.5 text-sm font-semibold transition border rounded-full text-body-1/70 border-base hover:bg-base-lvl-2"
        @click="router.visit('/housing/chores/screen')"
      >
        <i class="text-xs fa fa-expand"></i>
        {{ $t('Screen') }}
      </button>
      <span class="inline-flex items-center gap-2 px-3.5 py-1.5 text-sm font-semibold capitalize rounded-full text-body-1/70 bg-base-lvl-2 whitespace-nowrap">
        <i class="text-xs fa fa-calendar-day"></i>
        {{ todayLabel }}
      </span>
    </div>

    <!-- Mobile: one flat, filterable list (Maple-style). Desktop/kiosk keep lanes. -->
    <template v-if="context.isMobile && !kiosk">
      <!-- filters: status/date segment + person selector -->
      <div class="flex flex-col gap-2 mb-3">
        <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1">
          <button
            v-for="s in statusChips"
            :key="s.key"
            type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border whitespace-nowrap transition shrink-0"
            :class="statusFilter === s.key ? 'bg-primary text-white border-primary' : 'text-body-1/70 border-base bg-base-lvl-2 hover:text-body'"
            @click="statusFilter = s.key"
          >
            {{ $t(s.label) }}
            <span :class="statusFilter === s.key ? 'text-white/80' : 'text-body-1/40'">{{ s.count }}</span>
          </button>
        </div>
        <div class="flex gap-1.5 overflow-x-auto no-scrollbar -mx-1 px-1">
          <button
            type="button"
            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full border whitespace-nowrap transition shrink-0"
            :class="personFilter === null ? 'bg-base-lvl-3 text-body border-base-lvl-1' : 'text-body-1/70 border-base bg-base-lvl-2'"
            @click="personFilter = null"
          >{{ $t('All') }}</button>
          <button
            v-for="(m, idx) in users"
            :key="m.value"
            type="button"
            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full border whitespace-nowrap transition shrink-0"
            :class="String(personFilter) === String(m.value) ? 'bg-base-lvl-3 text-body border-base-lvl-1' : 'text-body-1/70 border-base bg-base-lvl-2'"
            @click="personFilter = m.value"
          >
            <span class="w-2.5 h-2.5 rounded-full" :style="{ background: memberColor(idx) }"></span>
            {{ m.name }}
            <span class="text-body-1/40">{{ personCount(m.value) }}</span>
          </button>
          <button
            v-if="hasUnassigned"
            type="button"
            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full border whitespace-nowrap transition shrink-0"
            :class="personFilter === '__unassigned__' ? 'bg-base-lvl-3 text-body border-base-lvl-1' : 'text-body-1/70 border-base bg-base-lvl-2'"
            @click="personFilter = '__unassigned__'"
          >{{ $t('Unassigned') }}</button>
        </div>
      </div>

      <!-- flat list of chore cards (same card style as the lanes) -->
      <div class="flex flex-col gap-2.5 pb-24">
        <div
          v-for="item in flatItems"
          :key="item.id"
          class="flex items-center gap-3 p-3 transition border shadow-sm rounded-xl bg-base-lvl-3 border-base"
          :class="{ 'opacity-60': item.is_done }"
        >
          <button
            type="button"
            class="flex items-center justify-center flex-shrink-0 transition border-2 rounded-full w-9 h-9"
            :style="item.is_done ? { background: ownerMeta(item).color, borderColor: ownerMeta(item).color } : { borderColor: ownerMeta(item).color }"
            :disabled="busy[item.id]"
            @click="toggleDone(item)"
          >
            <i v-if="item.is_done" class="text-white fa fa-check"></i>
          </button>
          <div class="flex-1 min-w-0">
            <p class="font-semibold line-clamp-2 text-body" :class="{ 'line-through': item.is_done }">{{ item.title }}</p>
            <div class="flex items-center gap-2 mt-1">
              <div class="relative">
                <button
                  type="button"
                  class="inline-flex items-center gap-1 px-2 py-0.5 text-xs transition rounded-full bg-base-lvl-1 text-body-1/50 hover:text-body"
                  @click.stop="openRec = openRec === item.id ? null : item.id"
                >
                  <i class="fa fa-redo text-[9px]"></i>
                  {{ $t(recurrenceKey(item)) }}
                </button>
                <div
                  v-if="openRec === item.id"
                  class="absolute left-0 z-20 py-1 mt-1 border shadow-lg w-36 rounded-xl bg-base-lvl-1 border-base"
                >
                  <button
                    v-for="p in recurrencePresets"
                    :key="p.key"
                    type="button"
                    class="flex items-center w-full gap-2 px-3 py-1.5 text-sm text-left hover:bg-base-lvl-2"
                    :class="recurrenceKey(item) === p.label ? 'text-primary' : 'text-body'"
                    @click="setRecurrence(item, p.key)"
                  >{{ $t(p.label) }}</button>
                </div>
              </div>
              <span v-if="isOverdue(item)" class="text-xs font-semibold text-error">{{ $t('Overdue') }}</span>
              <span v-else-if="item.due_date" class="text-xs text-body-1/50">{{ item.due_date }}</span>
            </div>
          </div>
          <div class="relative flex-shrink-0">
            <button
              type="button"
              class="flex items-center justify-center rounded-full w-8 h-8"
              :title="$t('Owner')"
              @click.stop="openAssign = openAssign === item.id ? null : item.id"
            >
              <span
                v-if="item.owner"
                class="flex items-center justify-center w-8 h-8 text-white rounded-full text-[11px] font-bold"
                :style="{ background: ownerMeta(item).color }"
              >{{ ownerMeta(item).initial }}</span>
              <span
                v-else
                class="flex items-center justify-center border border-dashed rounded-full w-8 h-8 border-base text-body-1/40"
              ><i class="text-xs fa fa-user-plus"></i></span>
            </button>
            <div
              v-if="openAssign === item.id"
              class="absolute right-0 z-20 w-40 py-1 mt-1 border shadow-lg rounded-xl bg-base-lvl-1 border-base"
            >
              <button
                v-for="(m, idx) in users"
                :key="m.value"
                type="button"
                class="flex items-center w-full gap-2 px-3 py-1.5 text-sm text-left hover:bg-base-lvl-2 text-body"
                @click="assign(item, m.value)"
              >
                <span class="flex items-center justify-center w-5 h-5 text-white rounded-full text-[10px] font-bold" :style="{ background: memberColor(idx) }">{{ (m.name || '?').slice(0, 1).toUpperCase() }}</span>
                <span class="truncate">{{ m.name }}</span>
              </button>
              <button
                type="button"
                class="flex items-center w-full gap-2 px-3 py-1.5 text-sm text-left hover:bg-base-lvl-2 text-body-1/60"
                @click="assign(item, null)"
              >
                <i class="w-5 text-xs text-center fa fa-user-slash"></i>
                <span>{{ $t('Unassigned') }}</span>
              </button>
            </div>
          </div>
        </div>
        <p v-if="!flatItems.length" class="py-10 text-sm text-center text-body-1/50">
          {{ $t('Nothing here yet') }}
        </p>
      </div>

      <!-- bottom add input, floats above the tab bar -->
      <div
        class="fixed inset-x-0 z-20 px-3 pt-4 pb-2 bg-gradient-to-t from-base-lvl-1 via-base-lvl-1/95 to-transparent"
        :style="{ bottom: 'calc(3.75rem + env(safe-area-inset-bottom))' }"
      >
        <input
          v-model="flatDraft"
          @keyup.enter="addFlat"
          type="text"
          :placeholder="'+ ' + $t('Add item')"
          class="w-full px-4 py-3 text-sm transition border rounded-xl border-base bg-base-lvl-3 text-body placeholder:text-body-1/40 focus:outline-none focus:border-primary"
        />
      </div>
    </template>

    <div v-else class="flex flex-col gap-4 pb-4 md:flex-row md:overflow-x-auto">
      <div
        v-for="lane in lanes"
        :key="lane.key"
        class="w-full md:w-72 md:flex-shrink-0 border shadow-sm rounded-2xl bg-base-lvl-2 border-base"
      >
        <div class="relative px-4 pt-4 pb-3 overflow-hidden rounded-t-2xl bg-base-lvl-3/40">
          <span class="absolute top-0 left-0 right-0 h-1" :style="{ background: lane.color }"></span>
          <div class="flex items-center justify-between">
            <div class="flex items-center min-w-0 gap-2.5">
              <span
                class="flex items-center justify-center flex-shrink-0 text-sm font-bold text-white rounded-full shadow-sm w-9 h-9 ring-2 ring-white/10"
                :style="{ background: lane.color }"
              >{{ laneInitial(lane) }}</span>
              <span class="font-semibold truncate text-body">{{ lane.unassigned ? $t('Unassigned') : lane.name }}</span>
            </div>
            <div
              class="flex items-center flex-shrink-0 gap-1.5 px-2.5 py-1 rounded-full bg-base-lvl-1"
              :title="$t('Points')"
            >
              <i class="text-xs fa fa-star" :style="{ color: lane.color }"></i>
              <span class="text-sm font-bold leading-none text-body">{{ donePoints(lane) }}</span>
              <span class="text-xs leading-none text-body-1/50">/ {{ lane.items.length }}</span>
            </div>
          </div>
          <div class="h-1.5 mt-3 overflow-hidden rounded-full bg-base-lvl-1">
            <div class="h-full transition-all duration-500 rounded-full" :style="{ width: lanePct(lane) + '%', background: lane.color }"></div>
          </div>
        </div>

        <div class="p-3 space-y-3 min-h-[96px]">
          <div
            v-for="item in lane.items"
            :key="item.id"
            class="flex items-center gap-3 p-3 transition border shadow-sm rounded-xl bg-base-lvl-3 border-base"
            :class="{ 'opacity-60': item.is_done }"
          >
            <button
              type="button"
              class="flex items-center justify-center flex-shrink-0 transition border-2 rounded-full w-10 h-10"
              :style="item.is_done ? { background: lane.color, borderColor: lane.color } : { borderColor: lane.color }"
              :disabled="busy[item.id]"
              @click="toggleDone(item)"
            >
              <i v-if="item.is_done" class="text-white fa fa-check"></i>
            </button>
            <div class="flex-1 min-w-0">
              <p class="font-semibold line-clamp-2 text-body" :class="{ 'line-through': item.is_done }">{{ item.title }}</p>
              <div class="flex items-center gap-2 mt-1">
                <div class="relative">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs transition rounded-full bg-base-lvl-1 text-body-1/50 hover:text-body"
                    @click.stop="openRec = openRec === item.id ? null : item.id"
                  >
                    <i class="fa fa-redo text-[9px]"></i>
                    {{ $t(recurrenceKey(item)) }}
                  </button>
                  <div
                    v-if="openRec === item.id"
                    class="absolute left-0 z-20 py-1 mt-1 border shadow-lg w-36 rounded-xl bg-base-lvl-1 border-base"
                  >
                    <button
                      v-for="p in recurrencePresets"
                      :key="p.key"
                      type="button"
                      class="flex items-center w-full gap-2 px-3 py-1.5 text-sm text-left hover:bg-base-lvl-2"
                      :class="recurrenceKey(item) === p.label ? 'text-primary' : 'text-body'"
                      @click="setRecurrence(item, p.key)"
                    >{{ $t(p.label) }}</button>
                  </div>
                </div>
                <span v-if="item.due_date" class="text-xs text-body-1/50">{{ item.due_date }}</span>
              </div>
            </div>
            <div class="relative flex-shrink-0">
              <button
                type="button"
                class="flex items-center justify-center rounded-full w-7 h-7"
                :title="$t('Owner')"
                @click.stop="openAssign = openAssign === item.id ? null : item.id"
              >
                <span
                  v-if="!lane.unassigned"
                  class="flex items-center justify-center w-7 h-7 text-white rounded-full text-[11px] font-bold"
                  :style="{ background: lane.color }"
                >{{ laneInitial(lane) }}</span>
                <span
                  v-else
                  class="flex items-center justify-center border border-dashed rounded-full w-7 h-7 border-base text-body-1/40"
                ><i class="text-xs fa fa-user-plus"></i></span>
              </button>
              <div
                v-if="openAssign === item.id"
                class="absolute right-0 z-20 w-40 py-1 mt-1 border shadow-lg rounded-xl bg-base-lvl-1 border-base"
              >
                <button
                  v-for="(m, idx) in users"
                  :key="m.value"
                  type="button"
                  class="flex items-center w-full gap-2 px-3 py-1.5 text-sm text-left hover:bg-base-lvl-2 text-body"
                  @click="assign(item, m.value)"
                >
                  <span class="flex items-center justify-center w-5 h-5 text-white rounded-full text-[10px] font-bold" :style="{ background: memberColor(idx) }">{{ (m.name || '?').slice(0, 1).toUpperCase() }}</span>
                  <span class="truncate">{{ m.name }}</span>
                </button>
                <button
                  type="button"
                  class="flex items-center w-full gap-2 px-3 py-1.5 text-sm text-left hover:bg-base-lvl-2 text-body-1/60"
                  @click="assign(item, null)"
                >
                  <i class="w-5 text-xs text-center fa fa-user-slash"></i>
                  <span>{{ $t('Unassigned') }}</span>
                </button>
              </div>
            </div>
          </div>
          <input
            v-model="draft[lane.key]"
            @keyup.enter="addChore(lane)"
            type="text"
            :placeholder="'+ ' + $t('Add item')"
            class="w-full px-3 py-2.5 text-sm transition bg-transparent border border-dashed rounded-xl border-base text-body placeholder:text-body-1/40 focus:outline-none focus:border-primary focus:bg-base-lvl-3"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Hide the horizontal scrollbar on the mobile filter-chip rows. */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
