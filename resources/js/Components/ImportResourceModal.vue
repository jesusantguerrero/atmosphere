<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" v-slot:default="{ close }" @close="$emit('update:show', false)">
        <div class="pb-4 bg-slate-600 sm:p-6 sm:pb-4 text-gray-200">
            <ImportHolder />
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-slate-700">
            <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
            <AtButton class="text-white bg-pink-400 h-10" @click="submit" rounded> Import </AtButton>
        </div>
    </modal>
</template>

<script setup>
    import Modal from '@/Jetstream/Modal.vue'
    import ImportHolder from '@/Components/organisms/ImportHolder.vue';
    import { AtButton } from "atmosphere-ui"

    defineProps({
            show: {
                default: false
            },
            maxWidth: {
                default: '2xl'
            },
            closeable: {
                default: true
            },
    });
</script>
