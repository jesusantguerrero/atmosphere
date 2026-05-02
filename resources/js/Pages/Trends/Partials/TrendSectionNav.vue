<script setup lang="ts">
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import SubmenuTab from "@/Components/atoms/SubmenuTab.vue";
import type { TrendOption, TrendSubTab } from "./trendOptions";

const props = defineProps<{
    sections: TrendOption[];
}>();

const currentPath = computed(() => document?.location?.pathname ?? "");

const matchesSection = (s: TrendOption, path: string): boolean => {
    if (s.url) {
        return s.url === path;
    }
    return s.subTabs?.some((st) => st.url === path) ?? false;
};

const activeSection = computed(() =>
    props.sections.find((s) => matchesSection(s, currentPath.value))
);

const sectionActiveFn = (s: TrendOption) => (path: string): boolean => matchesSection(s, path);
const subTabActiveFn = (st: TrendSubTab) => (path: string): boolean => st.url === path;

const handleTopClick = (s: TrendOption): void => {
    if (s.url) {
        router.visit(s.url);
        return;
    }
    if (!s.subTabs?.length) {
        return;
    }
    const onSub = s.subTabs.some((st) => st.url === currentPath.value);
    if (!onSub) {
        router.visit(s.subTabs[0].url);
    }
};

const handleSubClick = (st: TrendSubTab): void => {
    router.visit(st.url);
};
</script>

<template>
    <div class="w-full">
        <div class="flex justify-between w-full h-12 overflow-auto md:overflow-hidden">
            <SubmenuTab
                v-for="section in sections"
                :key="section.label"
                :value="section.url ?? section.subTabs?.[0]?.url"
                :is-selected="matchesSection(section, currentPath)"
                :is-active-function="sectionActiveFn(section)"
                class="text-xs text-center md:text-md whitespace-nowrap"
                @click="handleTopClick(section)"
            >
                {{ $t(section.label) }}
            </SubmenuTab>
            <div class="items-center justify-end hidden py-1 ml-auto space-x-2 text-xs md:flex">
                <slot name="actions" />
            </div>
        </div>

        <div
            v-if="activeSection?.subTabs?.length"
            class="flex w-full h-10 overflow-auto md:overflow-hidden border-t border-base-lvl-2 bg-base-lvl-2/40"
        >
            <SubmenuTab
                v-for="st in activeSection.subTabs"
                :key="st.url"
                :value="st.url"
                :is-selected="st.url === currentPath"
                :is-active-function="subTabActiveFn(st)"
                class="text-xs text-center md:text-md whitespace-nowrap"
                @click="handleSubClick(st)"
            >
                {{ $t(st.label) }}
            </SubmenuTab>
        </div>
    </div>
</template>
