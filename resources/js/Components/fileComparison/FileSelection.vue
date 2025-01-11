<script setup lang="ts">
import { VueFinalModal } from 'vue-final-modal'
import FileSelectionModal from '../FileSelectionModal.vue';

defineProps<{
  title?: string;
  files?: any;
  asset?: Record<string, string>
  processing?: boolean
}>()

const emit = defineEmits<{
  (e: 'confirm', payload: any): void,
  (e: 'close'): void
}>()
</script>

<template>
  <VueFinalModal
    class="flex items-center justify-center "
    content-class="flex flex-col max-w-3xl w-[48rem] p-4 mx-4 space-y-2 bg-white border rounded-lg dark:border-gray-700"
    overlay-transition="vfm-fade"
    content-transition="vfm-fade"
  >
    <FileSelectionModal 
      :title="title"
      :files="files" 
      :asset="asset"
      :processing="processing"
      @confirm="$emit('confirm', $event)"
      @close="$emit('close')"
    />
  </VueFinalModal>
</template>

<style>
.confirm-modal {
  display: flex;
  justify-content: center;
  align-items: center;
}
.confirm-modal-content {
  display: flex;
  flex-direction: column;
  padding: 1rem;
  background: #fff;
  border-radius: 0.5rem;
}
.confirm-modal-content > * + *{
  margin: 0.5rem 0;
}
.confirm-modal-content h1 {
  font-size: 1.375rem;
}
.confirm-modal-content button {
  margin: 0.25rem 0 0 auto;
  padding: 0 8px;
  border: 1px solid;
  border-radius: 0.5rem;
}
.dark .confirm-modal-content {
  background: #000;
}
</style>