<script setup lang="ts">
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { onKeyStroke, useDebounceFn } from '@vueuse/core';
import { endOfMonth, format, parseISO, startOfMonth } from 'date-fns';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue';

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

    if (item.type == 'accounts') {
        return `/finance/accounts/${item.id}`;
    }

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
            :aria-label="t('Search for anything')"
            aria-haspopup="dialog"
            :aria-expanded="state.isOpen"
            @click="open"
            class="flex items-center gap-2 px-3 py-1.5 text-sm transition-colors border rounded-md text-body-1/60 border-body-1/10 hover:text-body-1 hover:border-body-1/20"
        >
            <IMdiMagnify />
            <span class="hidden lg:inline">{{ t('Search for anything') }}</span>
            <span class="hidden px-1.5 py-0.5 text-xs rounded bg-base-lvl-2 lg:inline">⌘K</span>
        </button>

        <Dialog :open="state.isOpen" :initial-focus="searchInput" class="relative z-[1600]" @close="close">
            <div class="fixed inset-0 bg-black/60" aria-hidden="true" />
            <div class="fixed inset-0 flex items-start justify-center overflow-y-auto p-3 lg:px-6 lg:pt-[12vh]">
            <DialogPanel class="flex w-full max-h-[calc(100dvh-1.5rem)] flex-col overflow-hidden rounded-2xl border border-body-1/15 bg-base-lvl-3 text-body-1 shadow-2xl lg:max-w-xl">
                <header class="flex shrink-0 flex-col gap-3 border-b border-body-1/10 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <DialogTitle class="text-base font-semibold">{{ t('Search for anything') }}</DialogTitle>
                        <button
                            type="button"
                            class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg px-3 text-sm font-medium text-body-1 hover:bg-base-lvl-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                            @click="close"
                        >
                            {{ t('Close') }}
                        </button>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-body-1/20 bg-base-lvl-2 px-3 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
                    <IMdiMagnify class="shrink-0 text-xl text-body-1/70" />
                    <input
                        ref="searchInput"
                        v-model="state.searchText"
                        type="text"
                        :aria-label="t('Search transactions, accounts and payees')"
                        :placeholder="t('Search transactions, accounts and payees')"
                        class="min-h-[48px] min-w-0 w-full border-0 bg-transparent px-0 text-base text-body-1 placeholder:text-body-1/70 focus:ring-0"
                        @keydown.down.prevent="move(1)"
                        @keydown.up.prevent="move(-1)"
                        @keydown.enter.prevent="goTo(visibleResults[state.activeIndex])"
                    />
                    </div>
                </header>

                <nav v-if="tabs.length" class="flex shrink-0 gap-2 overflow-x-auto px-4 py-2 border-b border-body-1/10">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        @click="selectTab(tab)"
                        class="min-h-[44px] shrink-0 px-3 py-2 text-sm font-medium capitalize rounded-lg"
                        :class="state.selectedTab == tab ? 'bg-primary text-white' : 'bg-base-lvl-2 text-body-1/70'"
                    >
                        {{ t(tab) }}
                    </button>
                </nav>

                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain py-2 lg:max-h-80" aria-live="polite" :aria-busy="state.isLoading">
                    <button
                        v-for="(item, index) in visibleResults"
                        :key="`${item.type}-${item.id}`"
                        type="button"
                        class="block min-h-[48px] w-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary"
                        @click="goTo(item)"
                        @mouseenter="state.activeIndex = index"
                    >
                        <SearchResultItem :item="item" :active="state.activeIndex == index" />
                    </button>

                    <p v-if="state.isLoading" class="px-4 py-10 text-sm text-center text-body-1/75">
                        {{ t('Searching...') }}
                    </p>
                    <p
                        v-else-if="hasSearched && !visibleResults.length"
                        class="px-4 py-10 text-sm text-center text-body-1/75"
                    >
                        {{ t('No results found') }}
                    </p>
                    <p v-else-if="!hasSearched" class="px-4 py-10 text-sm text-center text-body-1/75">
                        {{ t('Type at least 3 characters') }}
                    </p>
                </div>

                <footer class="hidden shrink-0 gap-4 px-4 py-3 text-xs border-t text-body-1/70 border-body-1/10 lg:flex">
                    <span>↑↓ {{ t('Navigate') }}</span>
                    <span>↵ {{ t('Open') }}</span>
                    <span>esc {{ t('Close') }}</span>
                </footer>
            </DialogPanel>
            </div>
        </Dialog>
    </div>
</template>
