
<script lang="ts" setup>
    import Modal from './Modal.vue'

const emit = defineEmits(['close']);

const { 
    maxWidth = '2xl',
    closeable = true
} = defineProps<{
    title?: string
    content?: string
    show: boolean
    maxWidth?: string;
    closeable?: boolean
}>()

function close() {
    emit('close')
}
</script>

<template>
    <modal :show="show" :max-width="maxWidth" :closeable="closeable" @close="close">
        <div class="px-4 pt-5 pb-4 bg-base-lvl-1 sm:p-6 sm:pb-4">
            <div class="">
                <header class="flex items-center w-full ">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 bg-red-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="w-6 h-6 text-red-600" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h4 class="ml-2 text-lg ">
                        <slot name="title">
                            {{ title }}
                        </slot>
                    </h4>
                </header>
                
                <div class="mt-4 text-center sm:mt-0 sm:ml-4 sm:text-left">
                    <div class="mt-6">
                        <slot name="content">
                            {{  content  }}
                        </slot>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 text-right bg-gray-100">
            <slot name="footer">
            </slot>
        </div>
    </modal>
</template>

