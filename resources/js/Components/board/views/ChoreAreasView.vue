<script setup lang="ts">
/**
 * ChoreAreasView — Tody-style "chores by area" for the household chores page.
 * A grid of house areas, each with a status dot driven by its most urgent
 * chore; tapping an area lists its chores by urgency with a freshness bar,
 * a done button and an area picker. Data comes from /housing/chores/areas.
 */
import { computed, onMounted, ref } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";

type ChoreStatus = "fresh" | "due_soon" | "due_today" | "overdue";
type ChoreColor = "green" | "yellow" | "orange" | "red";

interface AreaChore {
    id: number;
    title: string;
    area: string;
    is_done: boolean;
    interval_days: number | null;
    last_done_at: string | null;
    due_date: string;
    days_left: number;
    status: ChoreStatus;
    color: ChoreColor;
    freshness: number;
}

interface ChoreAreaSummary {
    key: string;
    label: string;
    total: number;
    counts: Record<ChoreStatus, number>;
    worst_status: ChoreStatus | null;
    color: ChoreColor | null;
    max_overdue_days: number;
}

const props = defineProps<{
    boardId: number;
}>();

const AREA_META: Record<string, { label: string; icon: string }> = {
    kitchen: { label: "Kitchen", icon: "fa-utensils" },
    bathroom: { label: "Bathroom", icon: "fa-bath" },
    bedroom: { label: "Bedroom", icon: "fa-bed" },
    living: { label: "Living room", icon: "fa-couch" },
    laundry: { label: "Laundry", icon: "fa-tshirt" },
    kids: { label: "Kids", icon: "fa-child" },
    general: { label: "General", icon: "fa-home" },
};

const DOT_CLASSES: Record<ChoreColor, string> = {
    green: "bg-emerald-500",
    yellow: "bg-amber-400",
    orange: "bg-orange-500",
    red: "bg-red-500",
};

const areas = ref<ChoreAreaSummary[]>([]);
const chores = ref<AreaChore[]>([]);
const isLoading = ref(true);
const hasError = ref(false);
const selectedAreaKey = ref<string | null>(null);
const busyChoreIds = ref<Record<number, boolean>>({});

const selectedArea = computed(() =>
    areas.value.find((area) => area.key === selectedAreaKey.value) ?? null,
);

const selectedAreaChores = computed(() =>
    chores.value.filter((chore) => chore.area === selectedAreaKey.value),
);

const areaLabel = (key: string) => AREA_META[key]?.label ?? key;
const areaIcon = (key: string) => AREA_META[key]?.icon ?? "fa-home";
const dotClass = (color: ChoreColor | null) =>
    color ? DOT_CLASSES[color] : "bg-gray-300 dark:bg-gray-600";

async function loadAreas() {
    hasError.value = false;
    try {
        const { data } = await axios.get("/housing/chores/areas");
        areas.value = data.areas;
        chores.value = data.chores;
    } catch (e) {
        hasError.value = true;
    } finally {
        isLoading.value = false;
    }
}

async function toggleDone(chore: AreaChore) {
    if (busyChoreIds.value[chore.id]) return;
    busyChoreIds.value[chore.id] = true;
    try {
        await axios.put(`/housing/plans/${props.boardId}/items/${chore.id}`, {
            is_done: !chore.is_done,
        });
        await loadAreas();
        router.reload({ only: ["chores"], preserveScroll: true });
    } finally {
        busyChoreIds.value[chore.id] = false;
    }
}

async function moveToArea(chore: AreaChore, area: string) {
    if (area === chore.area || busyChoreIds.value[chore.id]) return;
    busyChoreIds.value[chore.id] = true;
    try {
        await axios.patch(`/housing/chores/${chore.id}/area`, { area });
        await loadAreas();
    } finally {
        busyChoreIds.value[chore.id] = false;
    }
}

onMounted(loadAreas);
</script>

<template>
    <section class="flex flex-col w-full gap-4 pb-20">
        <p v-if="isLoading" class="py-10 text-center text-body-1/60">
            {{ $t("Loading…") }}
        </p>
        <p v-else-if="hasError" class="py-10 text-center text-body-1/60">
            {{ $t("Could not load the areas.") }}
        </p>

        <!-- Area grid -->
        <div
            v-else-if="!selectedArea"
            class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
        >
            <button
                v-for="area in areas"
                :key="area.key"
                type="button"
                class="relative flex flex-col items-start gap-3 p-4 text-left transition border shadow-sm rounded-xl bg-base-lvl-3 border-base hover:bg-base-lvl-2"
                :class="{ 'opacity-60': !area.total }"
                @click="selectedAreaKey = area.key"
            >
                <span
                    class="absolute w-3 h-3 rounded-full top-4 right-4"
                    :class="dotClass(area.color)"
                ></span>
                <span
                    class="flex items-center justify-center w-10 h-10 rounded-full bg-base-lvl-2 text-body-1/70"
                >
                    <i class="fa" :class="areaIcon(area.key)"></i>
                </span>
                <span class="flex flex-col">
                    <span class="font-semibold text-body">{{
                        $t(areaLabel(area.key))
                    }}</span>
                    <span class="text-xs text-body-1/60">
                        <template v-if="!area.total">{{
                            $t("No chores")
                        }}</template>
                        <template v-else-if="area.counts.overdue">{{
                            $t("{count} chores overdue", {
                                count: area.counts.overdue,
                            })
                        }}</template>
                        <template v-else-if="area.counts.due_today">{{
                            $t("{count} chores due today", {
                                count: area.counts.due_today,
                            })
                        }}</template>
                        <template v-else>{{ $t("All fresh") }}</template>
                    </span>
                </span>
            </button>
        </div>

        <!-- Chores of one area -->
        <div v-else class="flex flex-col gap-3">
            <header class="flex items-center gap-3">
                <button
                    type="button"
                    class="flex items-center justify-center w-9 h-9 transition rounded-full text-body-1/70 bg-base-lvl-2 hover:text-body"
                    :aria-label="$t('Back to areas')"
                    @click="selectedAreaKey = null"
                >
                    <i class="fa fa-arrow-left"></i>
                </button>
                <span
                    class="w-3 h-3 rounded-full"
                    :class="dotClass(selectedArea.color)"
                ></span>
                <h3 class="text-lg font-bold text-body">
                    {{ $t(areaLabel(selectedArea.key)) }}
                </h3>
            </header>

            <p
                v-if="!selectedAreaChores.length"
                class="py-8 text-center text-body-1/60"
            >
                {{ $t("No chores in this area yet.") }}
            </p>

            <article
                v-for="chore in selectedAreaChores"
                :key="chore.id"
                class="flex items-center gap-3 p-3 transition border shadow-sm rounded-xl bg-base-lvl-3 border-base"
            >
                <button
                    type="button"
                    class="flex items-center justify-center flex-shrink-0 transition border-2 rounded-full w-9 h-9"
                    :class="
                        chore.is_done
                            ? 'bg-emerald-500 border-emerald-500'
                            : 'border-base-lvl-1 hover:border-emerald-500'
                    "
                    :disabled="busyChoreIds[chore.id]"
                    :aria-label="$t('Mark as done')"
                    @click="toggleDone(chore)"
                >
                    <i v-if="chore.is_done" class="text-white fa fa-check"></i>
                </button>

                <div class="flex flex-col flex-1 min-w-0 gap-1.5">
                    <div class="flex items-baseline justify-between gap-2">
                        <p class="font-semibold truncate text-body">
                            {{ chore.title }}
                        </p>
                        <span
                            class="text-xs font-semibold whitespace-nowrap"
                            :class="{
                                'text-red-600 dark:text-red-400':
                                    chore.color === 'red',
                                'text-orange-600 dark:text-orange-400':
                                    chore.color === 'orange',
                                'text-amber-600 dark:text-amber-400':
                                    chore.color === 'yellow',
                                'text-body-1/60': chore.color === 'green',
                            }"
                        >
                            <template v-if="chore.days_left < -1">{{
                                $t("{count} days overdue", {
                                    count: -chore.days_left,
                                })
                            }}</template>
                            <template v-else-if="chore.days_left === -1">{{
                                $t("1 day overdue")
                            }}</template>
                            <template v-else-if="chore.days_left === 0">{{
                                $t("Scheduled today")
                            }}</template>
                            <template v-else-if="chore.days_left === 1">{{
                                $t("In 1 day")
                            }}</template>
                            <template v-else>{{
                                $t("In {count} days", {
                                    count: chore.days_left,
                                })
                            }}</template>
                        </span>
                    </div>
                    <div
                        class="w-full h-1.5 overflow-hidden rounded-full bg-base-lvl-1"
                    >
                        <div
                            class="h-full transition-all rounded-full"
                            :class="dotClass(chore.color)"
                            :style="{
                                width: `${Math.max(chore.freshness * 100, 4)}%`,
                            }"
                        ></div>
                    </div>
                </div>

                <select
                    class="flex-shrink-0 py-1 pl-2 pr-7 text-xs border rounded-md bg-base-lvl-2 border-base text-body-1/80"
                    :value="chore.area"
                    :disabled="busyChoreIds[chore.id]"
                    :aria-label="$t('Move to area')"
                    @change="
                        moveToArea(
                            chore,
                            ($event.target as HTMLSelectElement).value,
                        )
                    "
                >
                    <option
                        v-for="area in areas"
                        :key="area.key"
                        :value="area.key"
                    >
                        {{ $t(areaLabel(area.key)) }}
                    </option>
                </select>
            </article>
        </div>
    </section>
</template>
