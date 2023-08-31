<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase transition border border-gray-300 rounded-md shadow-sm bg-base-lvl-1 hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-gray-800 active:bg-gray-50 disabled:opacity-25">
        <slot></slot>
    </button>
</template>

<script lang="ts">
    export default {
        props: {
            type: {
                type: String,
                default: 'button',
            },
        }
    }
</script>
