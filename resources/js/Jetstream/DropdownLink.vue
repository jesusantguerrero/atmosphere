<template>
    <div>
        <button type="submit" class="block w-full px-4 py-2 text-sm leading-5 text-left text-gray-200 transition hover:bg-slate-500 focus:outline-none focus:bg-gray-100" v-if="as == 'button'">
            <slot></slot>
        </button>

        <a :href="href" class="block px-4 py-2 text-sm leading-5 text-gray-200 transition hover:bg-slate-500 focus:outline-none focus:bg-gray-100" v-else-if="as =='a'">
            <slot></slot>
        </a>

        <Link :href="href" class="block px-4 py-2 text-sm leading-5 text-gray-200 transition hover:bg-slate-500 focus:outline-none focus:bg-gray-100" v-else>
            <slot></slot>
        </Link>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/inertia-vue3"
defineProps({
    href: {
        type: String
    },
    as: {
        type: String
    }
})
</script>
