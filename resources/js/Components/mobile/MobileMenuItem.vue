<template>
    <Link
        :href="item.to || item.url"
        v-if="item.to || item.url"
        v-ripple
        class="inline-flex items-center justify-center w-full h-full transition-all"
    >
        <div
            class="flex flex-col items-center justify-center w-full h-full gap-0.5 text-white"
            :class="{ 'text-primary': isExact }"
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
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    item: {
        type: Object,
    },
    isActive: {
        type: Boolean,
    },
});

const isComponent = (property) => {
    return typeof property !== 'string';
};

const isExact = computed(() => {
    const url = props.item.url || props.item.to;
    return window.location.href.includes(url);
});
</script>
