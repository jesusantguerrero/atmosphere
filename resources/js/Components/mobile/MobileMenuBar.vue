<template>
<nav
    class="fixed inset-x-0 bottom-0 z-40 lg:hidden"
    :style="{ paddingBottom: 'env(safe-area-inset-bottom)' }"
>
    <div class="flex h-16 bg-gray-900 rounded-t-2xl shadow-[0_-4px_16px_rgba(0,0,0,0.18)]">
        <div
            v-for="(item, index) in menu"
            :key="item.name || index"
            class="flex-1 h-full"
        >
            <MobileMenuItem :item="item" v-if="item.url || item.to" />
            <div v-else class="flex items-center justify-center h-full">
                <ButtonCircle
                    class="-translate-y-5"
                    @click="$emit('action', item.action)"
                >
                    <component :is="item.icon" v-if="isComponent(item.icon)" />
                    <i v-else :class="item.icon" />
                </ButtonCircle>
            </div>
        </div>
    </div>
</nav>
</template>

<script setup>
import ButtonCircle from './ButtonCircle.vue';
import MobileMenuItem from './MobileMenuItem.vue';

defineProps({
    menu: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['action']);

const isComponent = (property) => {
    return typeof property !== 'string';
};
</script>

<style scoped lang="scss">
.router-link-active {
    span, .custom-icon {
        @apply text-primary;
    }
}
</style>
