<template>
<section class="flex bg-base-lvl-3 py-2 px-4" ref="formContainer">
    <div class="w-full m-0 p-0" v-if="isCreating">
        <LogerInput
            :value="modelValue"
            @update:modelValue="$emit('update:modelValue', $event)"
            class="bg-transparent border-none shadow-none"
            placeholder="New Category group"
        />
    </div>
    <div class="flex" :class="{'w-full justify-center': !isCreating}">
        <AtButton class="text-body-1 font-bold" @click="onCancel" v-if="isCreating">
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

<script setup>
import { computed, ref, onMounted, nextTick } from 'vue';
import { AtField, AtButton } from "atmosphere-ui"
import autoAnimate from '@formkit/auto-animate';

import LogerInput from '@/Components/atoms/LogerInput.vue';

const emit = defineEmits(['save'])
const props = defineProps({
    modelValue: {
        type: String
    }
})

const isCreating = ref(false)
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
            isCreating.value = false;
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
