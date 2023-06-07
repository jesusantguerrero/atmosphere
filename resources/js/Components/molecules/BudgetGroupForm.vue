<template>
<section class="flex px-4 py-2 bg-base-lvl-3" ref="formContainer">
    <div class="w-full p-0 m-0" v-if="isCreating">
        <LogerInput
            :model-value="modelValue"
            @update:modelValue="$emit('update:model-value', $event)"
            class="bg-transparent border-none shadow-none"
            :placeholder="placeholder"
        />
    </div>
    <div class="flex" :class="{'w-full justify-center': !isCreating}">
        <AtButton class="font-bold text-body-1" @click="onCancel" v-if="isCreating && showCancel">
            Cancel
        </AtButton>
        <AtButton
            class="justify-center text-center rounded-md"
            :class="[isCreating ? 'bg-primary min-w-max text-white': 'w-full']"
            @click="onCreateClicked"
        >
            {{ saveButtonLabel }}
        </AtButton>
    </div>
</section>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, nextTick } from 'vue';
import { AtButton } from "atmosphere-ui"
import autoAnimate from '@formkit/auto-animate';

import LogerInput from '@/Components/atoms/LogerInput.vue';

const emit = defineEmits(['save', 'update:model-value'])

const {
    modelValue,
    placeholder = "New Category group",
    mode = "placeholder",
    showCancel = true
} = defineProps<{
    modelValue: string;
    placeholder?: string;
    mode: 'edit'| 'placeholder',
    showCancel: boolean;
}>()

const isCreating = ref(mode == 'edit')
const saveButtonLabel = computed(() => {
    return isCreating.value ? 'Save' : 'Add Budget Group'
})

const onCancel = () => {
    isCreating.value = false
}

const onCreateClicked = () => {
    if (isCreating.value) {
        emit('save')
        nextTick(() => {
            if (placeholder) {
                isCreating.value = false;
            }
        })
    } else {
        isCreating.value = true;
    }
}

const formContainer = ref()
onMounted(() => {
    autoAnimate(formContainer.value);
})
</script>

<style scoped>
.form-group {
    margin: 0 0 0 0
}
</style>
