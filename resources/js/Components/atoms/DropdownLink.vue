<template>
    <div>
        <button type="submit" class="block w-full px-4 py-2 text-sm leading-5 text-left text-body transition hover:bg-base-lvl-2 focus:outline-none focus:bg-gray-100" v-if="as == 'button'">
            <slot></slot>
        </button>

        <a :href="href" class="block px-4 py-2 text-sm leading-5 text-body transition hover:bg-base-lvl-2 focus:outline-none focus:bg-gray-100" v-else-if="as =='a'">
            <slot></slot>
        </a>

        <Link :href="href" class="block px-4 py-2 text-sm leading-5 text-body transition hover:bg-base-lvl-2 focus:outline-none focus:bg-gray-100" v-else>
            <slot></slot>
        </Link>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3"
defineProps({
    href: {
        type: String
    },
    as: {
        type: String
    }
})
</script>
