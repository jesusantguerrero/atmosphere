<template>
    <article class="rounded-md py-2">
        <header class="flex justify-between">
            <AtFieldCheck
                v-model="state.isActive"
                :label="label"
                class="w-full rounded-md py-4 inline-block"
            />
        </header>

        <section v-if="state.isActive" class="px-4 mt-4 space-y-2">
            <div class="flex space-x-4" v-for="(condition, index) in form.conditions" :key="`option-${index}`">
                <NSelect
                    filterable
                    clearable
                    v-model:value="condition.operator"
                    :default-expand-all="true"
                    :options="options"
                    size="large"
                />
                <LogerInput v-model="condition.value" @blur="updateValue" />
                <LogerTabButton class="rounded-md" @click="remove(index)" v-if="isLast(index)">
                    -
                </LogerTabButton>
                <LogerTabButton class="rounded-md" @click="add">
                    +
                </LogerTabButton>
            </div>
        </section>
    </article>
</template>

<script setup>
import { AtFieldCheck } from "atmosphere-ui";
import { reactive } from "vue";
import { NSelect } from "naive-ui";

import LogerInput from "@/Components/atoms/LogerInput.vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";

const props = defineProps({
    label: {
        type: String
    },
    modelValue: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['update:modelValue'])

const options = [{
    value: 'contains',
    label: 'Contains'
}, {
    value: 'is',
    label: 'Is'
}]

const state = reactive({
    isActive: false
})

const form = reactive({
    conditions: props.modelValue
})

const updateValue = () => {
    emit('update:modelValue', [...form.conditions])
}

const add = () => {
    form.conditions.push({
        operator: '',
        value: ''
    })
}

const remove = (index) => {
    form.conditions.splice(index)
}

const isLast = (index) => {
    return index != 0 && index == form.conditions.length - 1
}
</script>
