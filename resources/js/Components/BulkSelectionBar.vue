<script lang="ts">
export default {
    props: {
        selectedItems: {
            type: Array,
            required: true
        }
    }
}
</script>

<template>
    <article  class="bulk-selection-bar rounded-md justify-between">
        <section class="flex items-center">
            <span class="font-bold mr-1">
                {{ selectedItems.length }}
            </span>
            Items selected
        </section>
        <section>
            <span class="ml-2 toolbar-buttons cursor-pointer" @click="$emit('delete-pressed')">
                <i class="fa fa-trash"></i>
            </span>
        </section>
    </article>
</template>


<style lang="scss" scoped>
.bulk-selection-bar {
    @apply flex bg-gray-800 text-white px-5 py-3 fixed z-50 bottom-10 right-5;
    width: calc(100% - 22.5rem);
    transition: all ease .3s;
}
</style>
