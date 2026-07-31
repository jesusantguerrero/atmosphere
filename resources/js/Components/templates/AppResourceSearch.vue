<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { onKeyStroke, useDebounceFn } from '@vueuse/core';
import { endOfMonth, format, parseISO, startOfMonth } from 'date-fns';
import { useI18n } from 'vue-i18n';
import axios from 'axios';

import Modal from '@/Components/atoms/Modal.vue';
import SearchResultItem from './SearchResultItem.vue';

// Mirrors SearchController::MIN_LENGTH — below this the server returns nothing,
// so there's no point spending a request.
const MIN_LENGTH = 3;

const { t } = useI18n();

const state = reactive({
    isOpen: false,
    searchText: '',
    results: {} as Record<string, Record<string, any>[]>,
    selectedTab: 'all',
    activeIndex: 0,
    isLoading: false,
});

const searchInput = ref<HTMLInputElement | null>(null);

const tabs = computed(() => {
    const groups = Object.keys(state.results);

    return groups.length ? ['all', ...groups] : [];
});

const visibleResults = computed(() => {
    if (state.selectedTab == 'all') {
        return Object.values(state.results).flat();
    }

    return state.results[state.selectedTab] ?? [];
});

const hasSearched = computed(() => state.searchText.trim().length >= MIN_LENGTH);

// Each keystroke supersedes the previous request; without this guard a slow
// early response can land after a faster later one and show stale results.
let requestId = 0;

const runSearch = useDebounceFn(async (searchText: string) => {
    const currentRequest = ++requestId;

    try {
        const { data } = await axios.get('/search', { params: { search: searchText } });

        if (currentRequest !== requestId) {
            return;
        }

        state.results = Array.isArray(data) ? {} : data;
        state.selectedTab = 'all';
        state.activeIndex = 0;
    } finally {
        if (currentRequest === requestId) {
            state.isLoading = false;
        }
    }
}, 250);

watch(
    () => state.searchText,
    (searchText: string) => {
        if (searchText.trim().length < MIN_LENGTH) {
            requestId++;
            state.results = {};
            state.isLoading = false;

            return;
        }

        state.isLoading = true;
        runSearch(searchText.trim());
    }
);

const open = () => {
    state.isOpen = true;
    nextTick(() => searchInput.value?.focus());
};

const close = () => {
    state.isOpen = false;
    state.searchText = '';
    state.results = {};
};

const selectTab = (tab: string) => {
    state.selectedTab = tab;
    state.activeIndex = 0;
};

/**
 * Transactions land on the month they belong to — the transactions page
 * defaults to the current month, so an older result would otherwise open an
 * empty list. Payees keep the page default since what's wanted there is
 * recent activity.
 */
const urlFor = (item: Record<string, any>) => {
    const search = encodeURIComponent(item.title ?? '');

    if (item.type == 'payees') {
        return `/finance/transactions?search=${search}`;
    }

    const date = parseISO(String(item.date).slice(0, 10));
    const range = `${format(startOfMonth(date), 'yyyy-MM-dd')}~${format(endOfMonth(date), 'yyyy-MM-dd')}`;

    return `/finance/transactions?search=${search}&filter[date]=${range}`;
};

const goTo = (item?: Record<string, any>) => {
    if (!item) {
        return;
    }

    close();
    router.visit(urlFor(item));
};

const move = (offset: number) => {
    const total = visibleResults.value.length;

    if (!total) {
        return;
    }

    state.activeIndex = (state.activeIndex + offset + total) % total;
};

onKeyStroke(['k', 'K'], (event: KeyboardEvent) => {
    if (!(event.metaKey || event.ctrlKey)) {
        return;
    }

    event.preventDefault();
    state.isOpen ? close() : open();
});
</script>

<template>
    <div>
        <button
            type="button"
            @click="open"
            class="flex items-center gap-2 px-3 py-1.5 text-sm transition-colors border rounded-md text-body-1/60 border-body-1/10 hover:text-body-1 hover:border-body-1/20"
        >
            <IMdiMagnify />
            <span class="hidden lg:inline">{{ t('Search for anything') }}</span>
            <span class="hidden px-1.5 py-0.5 text-xs rounded bg-base-lvl-2 lg:inline">⌘K</span>
        </button>

        <Modal :show="state.isOpen" max-width="xl" @close="close">
            <section class="flex flex-col">
                <header class="flex items-center gap-3 px-4 py-3 border-b border-body-1/10">
                    <IMdiMagnify class="text-body-1/40" />
                    <input
                        ref="searchInput"
                        v-model="state.searchText"
                        type="text"
                        :placeholder="t('Search transactions and payees')"
                        class="w-full text-sm bg-transparent border-0 outline-none text-body-1 focus:ring-0"
                        @keydown.down.prevent="move(1)"
                        @keydown.up.prevent="move(-1)"
                        @keydown.enter.prevent="goTo(visibleResults[state.activeIndex])"
                    />
                </header>

                <nav v-if="tabs.length" class="flex gap-2 px-4 py-2 border-b border-body-1/10">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        @click="selectTab(tab)"
                        class="px-3 py-1 text-xs font-bold capitalize rounded-md"
                        :class="state.selectedTab == tab ? 'bg-primary text-white' : 'bg-base-lvl-2 text-body-1/70'"
                    >
                        {{ t(tab) }}
                    </button>
                </nav>

                <div class="py-2 overflow-auto max-h-80 min-h-[8rem]">
                    <button
                        v-for="(item, index) in visibleResults"
                        :key="`${item.type}-${item.id}`"
                        type="button"
                        class="block w-full"
                        @click="goTo(item)"
                        @mouseenter="state.activeIndex = index"
                    >
                        <SearchResultItem :item="item" :active="state.activeIndex == index" />
                    </button>

                    <p v-if="state.isLoading" class="py-8 text-sm text-center text-body-1/50">
                        {{ t('Searching...') }}
                    </p>
                    <p
                        v-else-if="hasSearched && !visibleResults.length"
                        class="py-8 text-sm text-center text-body-1/50"
                    >
                        {{ t('No results found') }}
                    </p>
                    <p v-else-if="!hasSearched" class="py-8 text-sm text-center text-body-1/50">
                        {{ t('Type at least 3 characters') }}
                    </p>
                </div>

                <footer class="flex gap-4 px-4 py-2 text-xs border-t text-body-1/50 border-body-1/10">
                    <span>↑↓ {{ t('Navigate') }}</span>
                    <span>↵ {{ t('Open') }}</span>
                    <span>esc {{ t('Close') }}</span>
                </footer>
            </section>
        </Modal>
    </div>
</template>
