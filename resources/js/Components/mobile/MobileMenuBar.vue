<template>
<div class="flex w-screen overflow fixed left-0 -bottom-1 h-14 bg-gray-900 rounded-t-2xl lg:hidden">
    <div v-for="item in menu" class="w-full h-full">
        <MobileMenuItem :item="item" v-if="item.url||item.to" />
        <div v-else class="relative mx-auto">
            <ButtonCircle class="mx-auto -translate-y-7" @click="$emit('action', item.action)">
                <component :is="item.icon" v-if="item.icon" />
            </ButtonCircle>
        </div>
    </div>
</div>
</template>

<script setup>
import { Link } from '@inertiajs/inertia-vue3';
import ButtonCircle from './ButtonCircle.vue';
import MobileMenuItem from './MobileMenuItem.vue';

defineProps({
    menu: {
        type: Array
    }
})

const isComponent = (property) => {
    return typeof property !== 'string'
}

</script>

<style scoped lang="scss">
.router-link-active {
    span, .custom-icon {
        @apply text-green-500;
    }
}
</style>
