<template>
    <Link :href="item.to||item.url" v-if="item.to||item.url"
    v-ripple
    class="inline-block h-full w-full transition-all overflow-hidden" v-auto-animate>
        <div class="flex flex-col items-center justify-center text-white  h-full w-full"

        :class="{'text-primary': isExact}">
            <div class="flex items-center justify-center text-lg">
                <component :is="item.icon" v-if="isComponent(item.icon)" class="text-xl custom-icon" />
                <i class="custom-icon" :class="item.icon" v-else />
            </div>
            <span class="block mt-1 text-xs" v-if="isExact">
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
        type: Object
    },
    isActive: {
        type: Boolean
    }
})

const isComponent = (property) => {
    return typeof property !== 'string'
}

const isExact = computed(() => {
    const url = props.item.url || props.item.to
    return window.location.href.includes(url)
})
</script>
