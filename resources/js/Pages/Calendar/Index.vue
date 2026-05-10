<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { AtDatePager } from 'atmosphere-ui';
import {
    format,
    startOfMonth,
    endOfMonth,
    startOfWeek,
    endOfWeek,
    addDays,
    isSameMonth,
    isSameDay,
    parseISO,
    isToday as dateFnsIsToday,
} from 'date-fns';

import AppLayout from '@/Components/templates/AppLayout.vue';
import LogerButton from '@/Components/atoms/LogerButton.vue';

interface CalendarEvent {
    id: string;
    planner_id: number | null;
    title: string;
    start: string;
    end: string | null;
    kind: string;
    status: string | null;
    total: number | null;
    source: string | null;
    notes: string | null;
    completed_at: string | null;
}

const props = defineProps<{
    events: CalendarEvent[];
    start: string;
    end: string;
}>();

// Current viewed month
const currentDate = ref(new Date(props.start + 'T12:00:00'));

// Navigate month
const onDateChange = (newDate: Date) => {
    currentDate.value = newDate;
    const s = format(startOfMonth(newDate), 'yyyy-MM-dd');
    const e = format(endOfMonth(newDate), 'yyyy-MM-dd');
    router.get(route('calendar'), { start: s, end: e }, { preserveState: true, preserveScroll: true });
};

// Build calendar grid (6 weeks max)
const calendarDays = computed(() => {
    const monthStart = startOfMonth(currentDate.value);
    const monthEnd = endOfMonth(currentDate.value);
    const calStart = startOfWeek(monthStart, { weekStartsOn: 1 }); // Monday
    const calEnd = endOfWeek(monthEnd, { weekStartsOn: 1 });

    const days: { date: Date; isCurrentMonth: boolean; isToday: boolean; events: CalendarEvent[] }[] = [];
    let day = calStart;

    while (day <= calEnd) {
        const dayStr = format(day, 'yyyy-MM-dd');
        const dayEvents = props.events.filter((e) => e.start === dayStr);

        days.push({
            date: new Date(day),
            isCurrentMonth: isSameMonth(day, currentDate.value),
            isToday: dateFnsIsToday(day),
            events: dayEvents,
        });

        day = addDays(day, 1);
    }

    return days;
});

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

// Kind → color classes
const kindColor = (kind: string): string => {
    const map: Record<string, string> = {
        billing: 'bg-warning/20 text-warning border-warning/30',
        mealplan: 'bg-secondary/20 text-secondary border-secondary/30',
        task: 'bg-primary/20 text-primary border-primary/30',
        utility: 'bg-accent/20 text-accent border-accent/30',
        relationship: 'bg-error/20 text-error border-error/30',
        occurrence: 'bg-body-1/20 text-body-1 border-body-1/30',
    };
    return map[kind] || 'bg-primary/20 text-primary border-primary/30';
};

const kindIcon = (kind: string): string => {
    const map: Record<string, string> = {
        billing: 'fa-credit-card',
        mealplan: 'fa-utensils',
        task: 'fa-check-circle',
        utility: 'fa-bolt',
        relationship: 'fa-heart',
        occurrence: 'fa-repeat',
    };
    return map[kind] || 'fa-calendar-day';
};

// Selected day for detail panel
const selectedDay = ref<Date | null>(null);
const selectedDayEvents = computed(() => {
    if (!selectedDay.value) return [];
    const dayStr = format(selectedDay.value, 'yyyy-MM-dd');
    return props.events.filter((e) => e.start === dayStr);
});

const selectDay = (date: Date) => {
    selectedDay.value = isSameDay(date, selectedDay.value ?? new Date(0)) ? null : date;
};

// Add planner item form
const showAddForm = ref(false);
const addForm = useForm({
    name: '',
    date: '',
    notes: '',
    total: null as number | null,
    source: '',
});

const openAddForm = (date?: Date) => {
    addForm.reset();
    if (date) {
        addForm.date = format(date, 'yyyy-MM-dd');
    }
    showAddForm.value = true;
};

const submitAdd = () => {
    addForm.post(route('planner.store'), {
        onSuccess: () => {
            showAddForm.value = false;
            addForm.reset();
        },
    });
};

const completePlanner = (plannerId: number) => {
    router.patch(route('planner.complete', plannerId), {}, { preserveScroll: true });
};

const deletePlanner = (plannerId: number) => {
    if (!confirm('Delete this item?')) return;
    router.delete(route('planner.destroy', plannerId), { preserveScroll: true });
};

const monthLabel = computed(() => format(currentDate.value, 'MMMM yyyy'));
</script>

<template>
    <AppLayout :title="$t('Calendar')">
        <main class="px-4 mx-auto mt-5 mb-10 max-w-screen-2xl sm:px-6 lg:px-8 space-y-4">
            <!-- Header -->
            <header class="flex items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-body">{{ $t('Calendar') }}</h1>
                    <p class="text-sm text-body-1/70">{{ monthLabel }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <AtDatePager
                        :model-value="currentDate"
                        next-mode="month"
                        format="MMM yyyy"
                        icons-only
                        size="sm"
                        @update:model-value="onDateChange"
                    />
                    <LogerButton variant="inverse" rounded @click="openAddForm()">
                        <i class="fa fa-plus mr-2" />
                        {{ $t('Add item') }}
                    </LogerButton>
                </div>
            </header>

            <div class="flex gap-4">
                <!-- Month grid -->
                <section class="flex-1">
                    <!-- Weekday headers -->
                    <div class="grid grid-cols-7 mb-1">
                        <div
                            v-for="day in weekdays"
                            :key="day"
                            class="text-center text-xs font-semibold text-body-1/60 py-2"
                        >
                            {{ day }}
                        </div>
                    </div>

                    <!-- Day cells -->
                    <div class="grid grid-cols-7 border-t border-l border-base">
                        <button
                            v-for="(cell, idx) in calendarDays"
                            :key="idx"
                            type="button"
                            class="min-h-[5rem] md:min-h-[6rem] border-r border-b border-base p-1.5 text-left transition hover:bg-base-lvl-2"
                            :class="[
                                cell.isCurrentMonth ? 'bg-base-lvl-3' : 'bg-base-lvl-1/50',
                                cell.isToday ? 'ring-2 ring-inset ring-primary/40' : '',
                                selectedDay && isSameDay(cell.date, selectedDay) ? 'bg-primary/5' : '',
                            ]"
                            @click="selectDay(cell.date)"
                        >
                            <span
                                class="text-xs font-medium block mb-0.5"
                                :class="[
                                    cell.isToday ? 'bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center' : '',
                                    cell.isCurrentMonth ? 'text-body' : 'text-body-1/30',
                                ]"
                            >
                                {{ format(cell.date, 'd') }}
                            </span>
                            <div v-if="cell.events.length" class="space-y-0.5">
                                <div
                                    v-for="event in cell.events.slice(0, 3)"
                                    :key="event.id"
                                    class="text-[10px] leading-tight px-1 py-0.5 rounded border truncate"
                                    :class="[
                                        kindColor(event.kind),
                                        event.completed_at ? 'line-through opacity-50' : '',
                                    ]"
                                    :title="event.title"
                                >
                                    {{ event.title }}
                                </div>
                                <p
                                    v-if="cell.events.length > 3"
                                    class="text-[10px] text-body-1/50 pl-1"
                                >
                                    +{{ cell.events.length - 3 }} more
                                </p>
                            </div>
                        </button>
                    </div>
                </section>

                <!-- Detail panel -->
                <aside
                    v-if="selectedDay"
                    class="w-80 shrink-0 bg-base-lvl-3 rounded-lg border border-base p-4 self-start sticky top-4"
                >
                    <header class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-body">
                            {{ format(selectedDay, 'EEEE, MMM d') }}
                        </h3>
                        <button
                            type="button"
                            class="text-body-1/40 hover:text-body text-xs"
                            @click="selectedDay = null"
                        >
                            <i class="fa fa-times" />
                        </button>
                    </header>

                    <div v-if="selectedDayEvents.length" class="space-y-2">
                        <div
                            v-for="event in selectedDayEvents"
                            :key="event.id"
                            class="p-3 rounded-md border"
                            :class="[
                                kindColor(event.kind),
                                event.completed_at ? 'opacity-50' : '',
                            ]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fa text-xs" :class="kindIcon(event.kind)" />
                                        <span
                                            class="text-sm font-medium truncate"
                                            :class="event.completed_at ? 'line-through' : ''"
                                        >
                                            {{ event.title }}
                                        </span>
                                    </div>
                                    <p v-if="event.source" class="text-xs opacity-70 uppercase tracking-wide mt-0.5">
                                        {{ event.source }}
                                    </p>
                                    <p v-if="event.notes" class="text-xs opacity-70 mt-1 italic">
                                        {{ event.notes }}
                                    </p>
                                    <p v-if="event.total" class="text-sm font-bold mt-1">
                                        ${{ event.total.toLocaleString() }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions for planner items -->
                            <div
                                v-if="event.planner_id && !event.completed_at"
                                class="flex gap-2 mt-2 pt-2 border-t border-current/10"
                            >
                                <button
                                    type="button"
                                    class="text-xs hover:underline"
                                    @click="completePlanner(event.planner_id!)"
                                >
                                    <i class="fa fa-check mr-1" /> {{ $t('Done') }}
                                </button>
                                <button
                                    type="button"
                                    class="text-xs hover:underline opacity-70"
                                    @click="deletePlanner(event.planner_id!)"
                                >
                                    <i class="fa fa-trash mr-1" /> {{ $t('Delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6">
                        <p class="text-sm text-body-1/60">{{ $t('No events this day') }}</p>
                    </div>

                    <LogerButton
                        variant="neutral"
                        rounded
                        class="w-full mt-3 text-sm"
                        @click="openAddForm(selectedDay)"
                    >
                        <i class="fa fa-plus mr-2" />
                        {{ $t('Add to this day') }}
                    </LogerButton>
                </aside>
            </div>

            <!-- Add item modal (inline) -->
            <Teleport to="body">
                <div
                    v-if="showAddForm"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
                    @click.self="showAddForm = false"
                >
                    <div class="bg-base-lvl-3 rounded-lg border border-base p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-bold text-body mb-4">{{ $t('Add planner item') }}</h3>
                        <form class="space-y-3" @submit.prevent="submitAdd">
                            <div>
                                <label class="block text-xs font-medium text-body mb-1">{{ $t('Title') }} *</label>
                                <input
                                    v-model="addForm.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary"
                                    :placeholder="$t('e.g. School meeting')"
                                />
                                <p v-if="addForm.errors.name" class="text-xs text-error mt-1">{{ addForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-body mb-1">{{ $t('Date') }} *</label>
                                <input
                                    v-model="addForm.date"
                                    type="date"
                                    required
                                    class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-body mb-1">{{ $t('Notes') }}</label>
                                <textarea
                                    v-model="addForm.notes"
                                    rows="2"
                                    class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-body mb-1">{{ $t('Amount') }}</label>
                                    <input
                                        v-model.number="addForm.total"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-body mb-1">{{ $t('Source') }}</label>
                                    <input
                                        v-model="addForm.source"
                                        type="text"
                                        class="w-full px-3 py-2 text-sm border rounded-md bg-base-lvl-1 border-base text-body focus:outline-none focus:ring-2 focus:ring-primary"
                                        :placeholder="$t('e.g. school, family')"
                                    />
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <LogerButton variant="neutral" type="button" rounded @click="showAddForm = false">
                                    {{ $t('Cancel') }}
                                </LogerButton>
                                <LogerButton variant="inverse" type="submit" rounded :processing="addForm.processing">
                                    {{ $t('Add') }}
                                </LogerButton>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>
        </main>
    </AppLayout>
</template>
