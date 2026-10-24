<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

interface AgendaEvent {
    id: string;
    title: string;
    start: string;
    time?: string | null;
    completed_at?: string | null;
}

const props = defineProps<{ events: AgendaEvent[]; date: string }>();
const googleEvents = ref<AgendaEvent[]>([]);
const loading = ref(true);
const unavailable = ref(false);
const connected = ref<boolean | null>(null);
const clock = ref(new Date());
let clockInterval: ReturnType<typeof setInterval>;
const isPast = (event: AgendaEvent): boolean => Boolean(event.completed_at) || Boolean(event.time && new Date(`${props.date}T${event.time}:00`) < clock.value);
const visibleEvents = computed(() => [...(props.events ?? []), ...googleEvents.value]
    .filter(event => event.start === props.date)
    .sort((a, b) => Number(isPast(a)) - Number(isPast(b)) || (a.time ?? '').localeCompare(b.time ?? ''))
    .slice(0, 3));

const fetchGoogleEvents = async () => {
    loading.value = true;
    unavailable.value = false;
    try {
        const { data } = await axios.get('/calendar/google-events', { params: { start: props.date, end: props.date } });
        googleEvents.value = data.events ?? [];
        connected.value = Boolean(data.connected);
        unavailable.value = Boolean(data.error);
    } catch {
        unavailable.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    clockInterval = setInterval(() => { clock.value = new Date(); }, 60000);
    fetchGoogleEvents();
});
onUnmounted(() => clearInterval(clockInterval));
</script>

<template>
    <section class="min-w-0 rounded-xl border border-base bg-base-lvl-3 p-4">
        <header class="flex items-center justify-between gap-3 pb-3">
            <h2 class="text-sm font-bold text-body">{{ $t("Today's agenda") }}</h2>
            <Link :href="route('calendar')" class="inline-flex min-h-[44px] items-center text-sm text-primary hover:underline">{{ $t('See agenda') }} →</Link>
        </header>
        <div v-if="visibleEvents.length" class="grid gap-2">
            <Link v-for="event in visibleEvents" :key="event.id" :href="route('calendar')" class="flex min-w-0 items-start gap-3 rounded-lg bg-base-lvl-2 p-3 hover:bg-base-lvl-1" :class="isPast(event) && 'opacity-60'">
                <span class="shrink-0 text-sm font-semibold text-primary">{{ event.time ?? $t('All day') }}</span>
                <span class="min-w-0 text-sm text-body break-words" :class="event.completed_at && 'line-through'">{{ event.title }}</span>
            </Link>
        </div>
        <p v-else-if="loading" class="text-sm text-body-1/70">{{ $t('Loading agenda...') }}</p>
        <div v-else-if="unavailable" class="text-sm text-body-1/70">
            <p>{{ $t('We could not load your agenda.') }}
                <button type="button" class="ml-1 text-sm text-primary hover:underline" @click="fetchGoogleEvents">{{ $t('Retry') }}</button>
            </p>
        </div>
        <p v-else class="text-sm text-body-1/70">{{ $t('No events today. A clear day.') }}</p>
        <p v-if="unavailable && visibleEvents.length" class="pt-2 text-xs text-body-1/70">{{ $t('Google events could not be loaded.') }}</p>
    </section>
</template>
