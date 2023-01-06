<template>
<div class="flex w-full fixed bottom-0 h-14 bg-gray-900 rounded-t-2xl pt-2 lg:hidden">
    <div v-for="item in menu" class="w-full">
    <div>
        <Link :href="item.to||item.url" v-if="item.to||item.url">
            <div class="flex flex-col items-center justify-center text-white">
                <div class="flex items-center justify-center">
                    <component :is="item.icon" v-if="isComponent(item.icon)" class="text-xl custom-icon" />
                    <i class="custom-icon" :class="item.icon" v-else />
                </div>
                <span class="block mt-2 text-xs">
                    {{ item.label }}
                </span>
            </div>
        </Link>
        <div v-else class="relative mx-auto">
            <ButtonCircle class="mx-auto -translate-y-7">
                <component :is="item.icon" v-if="item.icon" />
            </ButtonCircle>
        </div>
    </div>
    </div>
</div>
</template>

<script setup>
import { Link } from '@inertiajs/inertia-vue3';
import ButtonCircle from './ButtonCircle.vue';

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
