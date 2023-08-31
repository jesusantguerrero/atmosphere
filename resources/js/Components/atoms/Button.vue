<template>
    <button :type="type" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25">
        <slot></slot>
    </button>
</template>

<script lang="ts">
    export default {
        props: {
            type: {
                type: String,
                default: 'submit',
            },
        }
    }
</script>
