<template>
<LogerTabButton @click="toggleMode()" :keep-active-mode="false">
     <div class="w-5 h-5 mr-1 text-body-1/80">
        <svg v-if="!modelValue" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--mdi-light" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M11.5 18c3.989 0 7.458-2.224 9.235-5.5A10.498 10.498 0 0 0 11.5 7a10.498 10.498 0 0 0-9.235 5.5A10.498 10.498 0 0 0 11.5 18Zm0-12a11.5 11.5 0 0 1 10.36 6.5A11.5 11.5 0 0 1 11.5 19a11.5 11.5 0 0 1-10.36-6.5A11.5 11.5 0 0 1 11.5 6Zm0 2a4.5 4.5 0 1 1 0 9a4.5 4.5 0 0 1 0-9Zm0 1a3.5 3.5 0 1 0 0 7a3.5 3.5 0 0 0 0-7Z"></path></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true" role="img" class="iconify iconify--mdi-light" preserveAspectRatio="xMidYMid meet" viewBox="0 0 24 24"><path fill="currentColor" d="M2.543 4.707L3.25 4L20 20.75l-.707.707l-3.348-3.348c-1.367.574-2.87.891-4.445.891a11.5 11.5 0 0 1-10.36-6.5a11.55 11.55 0 0 1 4.374-4.821L2.543 4.707ZM11.5 18c1.293 0 2.531-.234 3.675-.661l-1.129-1.128A4.5 4.5 0 0 1 7.79 9.954L6.244 8.408a10.55 10.55 0 0 0-3.98 4.092A10.498 10.498 0 0 0 11.5 18Zm9.235-5.5A10.498 10.498 0 0 0 11.5 7a10.49 10.49 0 0 0-3.305.53l-.783-.782A11.474 11.474 0 0 1 11.5 6a11.5 11.5 0 0 1 10.36 6.5a11.55 11.55 0 0 1-4.068 4.628l-.724-.724a10.552 10.552 0 0 0 3.667-3.904ZM11.5 8a4.5 4.5 0 0 1 3.904 6.74l-.74-.74A3.5 3.5 0 0 0 10 9.336l-.74-.74A4.48 4.48 0 0 1 11.5 8ZM8 12.5a3.5 3.5 0 0 0 5.324 2.988l-4.812-4.812A3.484 3.484 0 0 0 8 12.5Z"></path></svg>
     </div>
    {{ label }}
</LogerTabButton>
</template>

<script setup>
import LogerTabButton from "../atoms/LogerTabButton.vue";

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    label: {
        type: String
    }
})

const emit = defineEmits(['update:modelValue'])

const toggleMode = () => {
    emit('update:modelValue', !props.modelValue)
}

</script>
