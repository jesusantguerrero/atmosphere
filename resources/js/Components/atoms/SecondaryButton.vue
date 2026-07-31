<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-body-1 uppercase transition border border-base-lvl-2 rounded-md shadow-sm bg-base-lvl-1 hover:text-body-1 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-body active:bg-base-lvl-2 disabled:opacity-25">
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
