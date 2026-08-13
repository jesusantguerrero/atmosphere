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

const props = defineProps<{
  stages: any[];
  fields?: any[];
  boardId?: number;
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
</script>

<template>
  <div class="flex flex-col w-full pb-20">
    <div class="flex items-center justify-end mb-5">
      <span class="inline-flex items-center gap-2 px-3.5 py-1.5 text-sm font-semibold capitalize rounded-full text-body-1/70 bg-base-lvl-2">
        <i class="text-xs fa fa-calendar-day"></i>
        {{ todayLabel }}
      </span>
    </div>

    <div class="flex gap-4 pb-4 overflow-x-auto">
      <div
        v-for="lane in lanes"
        :key="lane.key"
        class="flex-shrink-0 border shadow-sm w-72 rounded-2xl bg-base-lvl-2 border-base"
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
              <p class="font-semibold truncate text-body" :class="{ 'line-through': item.is_done }">{{ item.title }}</p>
              <p v-if="item.due_date" class="text-xs text-body-1/60">{{ item.due_date }}</p>
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
