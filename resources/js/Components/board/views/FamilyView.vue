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
  kanbanData?: Record<string, any>;
  resourceName?: string;
}>();

const users = inject<any[]>('users', []);

// Same palette/offset as the Routine (Rutina) chips so a member reads with the
// same colour across the app.
const COLORS = ['#56C08A', '#6E9BE6', '#E8A54F', '#E87FA8', '#A98BE0', '#B79B82', '#5CC2C2', '#E0736F', '#6B7686'];
const memberColor = (idx: number) => COLORS[(idx + 3) % COLORS.length];

const onlyToday = ref(false);
const todayStr = new Date().toISOString().slice(0, 10);

const allItems = computed(() => (props.stages || []).flatMap((s: any) => s.items || []));

const isToday = (item: any) => (item.due_date || '').toString().slice(0, 10) === todayStr;

const visibleItems = computed(() => (onlyToday.value ? allItems.value.filter(isToday) : allItems.value));

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

const busy = ref<Record<number, boolean>>({});

async function toggleDone(item: any) {
  if (busy.value[item.id]) return;
  busy.value[item.id] = true;
  const next = !item.is_done;
  item.is_done = next; // optimistic
  try {
    await axios.put(`/housing/plans/${item.board_id}/items/${item.id}`, { is_done: next });
    router.reload({ preserveScroll: true });
  } catch (e) {
    item.is_done = !next; // revert on failure
  } finally {
    busy.value[item.id] = false;
  }
}
</script>

<template>
  <div class="w-full pb-20">
    <div class="flex items-center justify-end mb-4">
      <button
        type="button"
        class="px-4 py-1.5 text-sm font-semibold transition border rounded-full"
        :class="onlyToday ? 'bg-primary text-white border-primary' : 'text-body-1 border-base hover:bg-base-lvl-2'"
        @click="onlyToday = !onlyToday"
      >
        {{ $t('Today') }}
      </button>
    </div>

    <div class="flex gap-4 pb-4 overflow-x-auto">
      <div
        v-for="lane in lanes"
        :key="lane.key"
        class="flex-shrink-0 border w-72 rounded-2xl bg-base-lvl-2 border-base"
      >
        <div class="flex items-center justify-between px-4 py-3 border-b border-base">
          <div class="flex items-center gap-2 font-bold text-body">
            <span
              class="flex items-center justify-center w-8 h-8 text-sm font-bold text-white rounded-full"
              :style="{ background: lane.color }"
            >{{ laneInitial(lane) }}</span>
            <span>{{ lane.unassigned ? $t('Unassigned') : lane.name }}</span>
          </div>
          <div class="flex items-baseline gap-1" :title="$t('Points')">
            <span class="text-3xl font-black leading-none" :style="{ color: lane.color }">{{ donePoints(lane) }}</span>
            <span class="text-xs text-body-1/60">/ {{ lane.items.length }}</span>
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
            <div class="min-w-0">
              <p class="font-semibold truncate text-body" :class="{ 'line-through': item.is_done }">{{ item.title }}</p>
              <p v-if="item.due_date" class="text-xs text-body-1/60">{{ item.due_date }}</p>
            </div>
          </div>
          <p v-if="!lane.items.length" class="py-6 text-sm text-center text-body-1/40">—</p>
        </div>
      </div>
    </div>
  </div>
</template>
