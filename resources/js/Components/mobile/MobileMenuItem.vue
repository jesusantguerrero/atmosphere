<template>
    <Link
        :href="item.to || item.url"
        v-if="item.to || item.url"
        v-ripple
        class="inline-flex items-center justify-center w-full h-full transition-colors active:bg-body-1/10"
    >
        <div
            class="flex flex-col items-center justify-center w-full h-full gap-0.5 transition-colors"
            :class="isExact ? 'text-primary' : 'text-body-1/70'"
        >
            <div class="flex items-center justify-center">
                <component :is="item.icon" v-if="isComponent(item.icon)" class="text-lg custom-icon" />
                <i class="text-lg custom-icon" :class="item.icon" v-else />
            </div>
            <span class="block text-[10px] font-medium leading-none truncate max-w-[64px]">
                {{ item.label }}
            </span>
        </div>
    </Link>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    item: {
        type: Object,
    },
    isActive: {
        type: Boolean,
    },
});

const page = usePage();

const isComponent = (property) => {
    return typeof property !== 'string';
};

// Reactive active state. The previous version read window.location.href inside a
// computed with no reactive dependency, so the highlight only reflected the
// first page load and never moved on Inertia (SPA) navigation. page.url updates
// on every visit, so the active tab now tracks the current route.
const isExact = computed(() => {
    const url = props.item.url || props.item.to || '';
    if (! url) return false;
    const current = (page.url || '').split('?')[0];
    return current === url || current.startsWith(url.endsWith('/') ? url : url + '/');
});
</script>
