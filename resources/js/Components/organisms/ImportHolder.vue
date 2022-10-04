<template>
    <div class="relative flex items-end justify-center w-full h-32 transition border border-dashed rounded-md cursor-pointer border-primary hover:bg-base-lvl-2" role="button"
        @click="openFileSelector" relative
        @ondrop="onDrop"
    >
        <div class="absolute z-20 flex flex-col items-center justify-center w-full h-full px-5 py-2 bg-transparent">
            <input name="fileInput" type="file" ref="fileInputRef" class="hidden" @change="setFile" />
            <label for="fileInput" class="text-sm font-bold cursor-pointer text-base-300">
                {{ filePlaceholderText }}
            </label>
            <div v-if="!formData.file" class="mt-2">
                or
                <LogerButton variant="inverse"> Browse </LogerButton>
            </div>
        </div>
        <div class="absolute z-10 flex w-full h-full bg-primary/30" :style="progressStyle" />
        <small v-if="formData.hasErrors" class="text-red-300">  {{ formData.errors.file }}</small>
    </div>
</template>

<script setup>
import { Inertia } from "@inertiajs/inertia";
import { useForm } from "@inertiajs/inertia-vue3";
import { computed, ref } from "vue";
import LogerButton from "../atoms/LogerButton.vue";

const props = defineProps({
    placeholder: {
        type: String,
        default: 'Drag a CSV file here or upload via the button to import your transactions'
    },
    endpoint: {
        type: String,
        default: "/financial/import"
    }
})
const emit = defineEmits(['uploaded', 'error', 'canceled'])
const fileInputRef = ref()
const openFileSelector = () => {
    fileInputRef.value?.click()
}

const formData = useForm({
    file: null
})

const filePlaceholderText = computed(() => {
    return formData.file? formData.file.name : props.placeholder
})

const setFile = (evt) => {
    formData.file = evt.target.files.length ? evt.target.files[0] : null;
}

const clearFile = () => {
    fileInputRef.value.value = null
    formData.file = null
}

const processImport = async () => {
  if (!formData.file) {
    formData.setError('file', "You have to submit a file")
    return
  }

  formData.post(props.endpoint, formData.data(), {
    onSuccess() {
        emit('uploaded')
        clearFile()
    }
  })
};

const progressStyle = computed(() => {
    return {
        width: `${formData.progress?.percentage || 0 }%` || '0px'
    }
})

defineExpose({
    processImport
})
</script>
