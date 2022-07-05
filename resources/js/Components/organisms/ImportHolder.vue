<template>
    <div class="h-32 w-full relative rounded-md flex items-end  justify-center border-dashed border border-pink-500 cursor-pointer hover:bg-slate-500 transition" role="button" @click="openFileSelector" relative>
        <div class="w-full h-full absolute z-20 px-5 py-2 bg-transparent flex items-center justify-center">
            <input name="fileInput" type="file" ref="fileInputRef" class="hidden" @change="setFile" />
            <label for="fileInput" class="cursor-pointer text-slate-300 font-bold text-sm">
                {{ filePlaceholderText }}
            </label>
        </div>
        <div class="absolute h-full flex z-10 bg-pink-500/30 w-full" :style="progressStyle" />
        <small v-if="formData.hasErrors" class="text-red-300">  {{ formData.errors.file }}</small>
    </div>

</template>

<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
import { computed, ref } from "vue";

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
  formData.post(props.endpoint, {
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
