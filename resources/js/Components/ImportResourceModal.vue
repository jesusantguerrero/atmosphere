<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" v-slot:default="{ close }" @close="emitClose">
        <div class="pb-4 bg-base-600 sm:p-6 sm:pb-4 text-gray-200">
            <ImportHolder ref="importHolderRef" @uploaded="emitClose" endpoint="/finance/import"/>
        </div>

        <div class="px-6 py-4 space-x-3 text-right bg-base-700">
            <AtButton type="secondary" @click="close" rounded class="h-10"> Cancel </AtButton>
            <AtButton class="text-white bg-primary-400 h-10" @click="submit" rounded> Import </AtButton>
        </div>
    </modal>
</template>

<script setup>
    import Modal from '@/Jetstream/Modal.vue'
    import ImportHolder from '@/Components/organisms/ImportHolder.vue';
    import { AtButton } from "atmosphere-ui"
    import { ref } from 'vue';

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

    const emit = defineEmits(['update:show'])

    const importHolderRef = ref()
    const submit = () => {
        importHolderRef.value.processImport()
    }

    const emitClose = () => {
        emit('update:show', false)
    }
</script>
