<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { useDropZone } from "@vueuse/core";

const props = defineProps({
    isManual: {
        type: Boolean
    },
    file: {
        type: Object
    },
    processing: {
        type: Boolean
    },
    placeholder: {
        type: String,
        default: 'Drag a CSV file here or upload via the button to import your transactions'
    },
    endpoint: {
        type: String,
        default: "/financial/import"
    }
})
const emit = defineEmits(['uploaded', 'error', 'canceled', 'update:file'])

const fileInputRef = ref()
const openFileSelector = () => {
    fileInputRef.value?.click()
}
// DropZone functionality
const dropZoneRef = ref();
const onDrop = (files: any[]) => {
    formData.file = files?.length ? files[0] : null
}
const { isOverDropZone } = useDropZone(dropZoneRef, onDrop);

const formData = useForm({
    file: null
})


watch(() => formData.file, () => {
    emit('update:file', formData.file);
})
//  ui
const filePlaceholderText = computed(() => {
    return formData.file? formData.file.name : props.placeholder
})

const progressStyle = computed(() => {
    return {
        width: `${formData.progress?.percentage || 0 }%` || '0px'
    }
})

// file options
const setFile = (evt) => {
    formData.file = evt.target.files.length ? evt.target.files[0] : null;
}

const clearFile = () => {
    if (fileInputRef.value && fileInputRef.value?.value) {
        fileInputRef.value.value = null
    }
    formData.file = null
}

const processImport = async () => {
  if (!formData.file) {
    formData.setError('file', "You have to submit a file")
    return
  }

  if (formData.processing || props.processing) {
    return
  }

  if (props.isManual) {
      emit('uploaded', {
        endpoint: props.endpoint,
        file: formData.file
      })
  }

  formData.post(props.endpoint, {
    onSuccess() {
        emit('uploaded')
        clearFile()
    }
  })
};

defineExpose({
    processImport
})
</script>

<template>
    <div
        class="relative flex items-end justify-center w-full h-32 transition border border-dashed rounded-md cursor-pointer border-primary hover:bg-base-lvl-2"
        role="button"
        relative
        ref="dropZoneRef"
        :class="{'border-primary bg-primary/20': isOverDropZone}"
        @click="openFileSelector"
    >
        <div class="absolute z-20 flex flex-col items-center justify-center w-full h-full px-5 py-2 bg-transparent">
            <input name="fileInput" type="file" ref="fileInputRef" class="hidden" @change="setFile" />
            <label for="fileInput" class="text-sm font-bold cursor-pointer text-base-300">
                {{ filePlaceholderText }}
            </label>
            <div v-if="!formData.file" class="mt-2">
                or
                <button class="underline text-primary underline-offset-2"> Browse in your computer</button>
            </div>
        </div>
        <div class="absolute z-10 flex w-full h-full bg-primary/30" :style="progressStyle" />
        <small v-if="formData.hasErrors" class="text-red-300">  {{ formData.errors.file }}</small>
    </div>
</template>


