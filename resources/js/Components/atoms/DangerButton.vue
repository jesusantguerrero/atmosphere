<template>
    <button :type="type" class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition bg-red-600 border border-transparent rounded-md hover:bg-red-500 focus:outline-none focus:border-red-700 focus:ring focus:ring-red-200 active:bg-red-600 disabled:opacity-25">
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
