<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from "vue";
import { startOfMonth, endOfMonth, getMonth, getYear, setMonth, setYear } from "date-fns";
import { useI18n } from "vue-i18n";

/**
 * MonthStripYear
 *
 * Replaces AtDatePager's one-month-at-a-time pager with a horizontal strip
 * showing all 12 months of the current year plus a year selector.
 *
 * Layout:
 * - Desktop (md+): single row — [prev year] [12 month pills] [year] [next year]
 * - Mobile: two rows — [prev year] [year] [next year] on top, pills scroll
 *   horizontally below with the active pill auto-centered. Pills stay
 *   readable (min 52px each) instead of collapsing.
 *
 * Contract-compatible with AtDatePager: emits update:startDate,
 * update:endDate, and change([start, end]) — callers swap the component
 * without touching their handlers.
 */

const props = defineProps<{
    startDate?: Date | null;
    endDate?: Date | null;
}>();

const emit = defineEmits<{
    (e: "update:startDate", value: Date): void;
    (e: "update:endDate", value: Date): void;
    (e: "change", value: [Date, Date]): void;
}>();

const { locale } = useI18n();

const currentDate = computed(() => props.startDate ?? new Date());
const currentMonthIndex = computed(() => getMonth(currentDate.value));
const currentYear = computed(() => getYear(currentDate.value));

/**
 * Localized short month labels ("Jan"/"Feb" in EN, "Ene"/"Feb" in ES).
 * Derived from Intl so the component follows the app's active locale
 * without a hand-maintained translation table.
 */
const monthLabels = computed(() => {
    const formatter = new Intl.DateTimeFormat(locale.value, { month: "short" });
    return Array.from({ length: 12 }, (_, i) => {
        const label = formatter.format(new Date(2000, i, 1));
        return label.charAt(0).toUpperCase() + label.slice(1).replace(".", "");
    });
});

const selectMonth = (monthIndex: number): void => {
    const target = setMonth(currentDate.value, monthIndex);
    const start = startOfMonth(target);
    const end = endOfMonth(target);
    emit("update:startDate", start);
    emit("update:endDate", end);
    emit("change", [start, end]);
};

const shiftYear = (delta: number): void => {
    const target = setYear(currentDate.value, currentYear.value + delta);
    const start = startOfMonth(target);
    const end = endOfMonth(target);
    emit("update:startDate", start);
    emit("update:endDate", end);
    emit("change", [start, end]);
};

/**
 * Scroll the active pill into view on mount and whenever the selected
 * month changes. Only meaningful on mobile where pills overflow the
 * container; on desktop the whole strip is visible and this is a no-op.
 */
const stripRef = ref<HTMLElement | null>(null);
const scrollActiveIntoView = (): void => {
    if (!stripRef.value) {
        return;
    }
    const active = stripRef.value.querySelector<HTMLElement>('[data-active="true"]');
    if (active) {
        active.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "center" });
    }
};

onMounted(() => nextTick(scrollActiveIntoView));
watch(currentMonthIndex, () => nextTick(scrollActiveIntoView));
</script>

<template>
    <div
        class="flex flex-col md:flex-row md:items-center gap-2 md:gap-1 p-1.5 rounded-lg bg-base-lvl-2 border border-base"
    >
        <!-- Mobile-only: year controls stack above the pills so the
             chevrons and year label stay tap-friendly on narrow screens. -->
        <div class="flex md:hidden items-center justify-between px-1">
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded-md text-body-1 hover:bg-base-lvl-1 hover:text-body transition-colors"
                :aria-label="$t('Previous year')"
                @click="shiftYear(-1)"
            >
                <i class="fa fa-chevron-left text-xs" />
            </button>
            <span class="text-sm font-semibold text-body tabular-nums">{{ currentYear }}</span>
            <button
                type="button"
                class="flex items-center justify-center w-8 h-8 rounded-md text-body-1 hover:bg-base-lvl-1 hover:text-body transition-colors"
                :aria-label="$t('Next year')"
                @click="shiftYear(1)"
            >
                <i class="fa fa-chevron-right text-xs" />
            </button>
        </div>

        <!-- Desktop-only prev-year chevron; on mobile it lives in the
             stacked header above instead. -->
        <button
            type="button"
            class="hidden md:flex items-center justify-center w-7 h-7 rounded-md text-body-1 hover:bg-base-lvl-1 hover:text-body transition-colors"
            :aria-label="$t('Previous year')"
            @click="shiftYear(-1)"
        >
            <i class="fa fa-chevron-left text-xs" />
        </button>

        <!-- Pills: desktop lays them out in a 12-column grid so every
             month is instantly clickable; mobile falls back to a
             horizontally-scrollable strip with snap so the active month
             stays centered as the year changes. -->
        <div
            ref="stripRef"
            class="flex-1 flex md:grid md:grid-cols-12 gap-1 overflow-x-auto md:overflow-visible snap-x snap-mandatory scroll-smooth no-scrollbar"
        >
            <button
                v-for="(label, index) in monthLabels"
                :key="index"
                type="button"
                :data-active="index === currentMonthIndex"
                class="flex-shrink-0 md:flex-shrink min-w-[52px] md:min-w-0 py-1.5 px-3 md:px-1 snap-center rounded-md text-xs font-medium transition-colors"
                :class="[
                    index === currentMonthIndex
                        ? 'bg-primary text-white'
                        : 'text-body-1 hover:bg-base-lvl-1 hover:text-body'
                ]"
                @click="selectMonth(index)"
            >
                {{ label }}
            </button>
        </div>

        <!-- Desktop-only year label + next chevron. -->
        <div class="hidden md:flex items-center gap-1">
            <span class="text-xs font-medium text-body tabular-nums px-2">{{ currentYear }}</span>
            <button
                type="button"
                class="flex items-center justify-center w-7 h-7 rounded-md text-body-1 hover:bg-base-lvl-1 hover:text-body transition-colors"
                :aria-label="$t('Next year')"
                @click="shiftYear(1)"
            >
                <i class="fa fa-chevron-right text-xs" />
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Hide native scrollbar on mobile pill strip — the snap-x behavior +
   scroll-into-view auto-focus makes the scrollbar redundant and it
   otherwise adds a thin light seam on top of the dark surface. */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
