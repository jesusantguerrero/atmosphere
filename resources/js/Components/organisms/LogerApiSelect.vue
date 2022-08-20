<template>
<Multiselect
    :model-value="modelValue"
    filterable
    clearable
    remote
    size="large"
    :searchable="true"
    :placeholder="placeholder"
    :options="options"
    :loading="isLoading"
    v-bind="$attrs"
    :custom-label="customLabel"
    :show-no-results="false"
    :hide-selected="true"
    @open="handleSearch(' ')"
    @search-change="handleSearch"
    @update:modelValue="$emit('update:modelValue', $event)"
>
    <template #action>
      <slot name="action" />
    </template>
</Multiselect>
</template>


<script setup>
import { ref } from "vue";
import { debounce } from "lodash";

const props = defineProps({
    modelValue: {
        type: [Object]
    },
    endpoint: {
        type: String
    },
    placeholder: {
        type: String
    },
    allowCreate: {
        type: Boolean
    },
    customLabel: {
        type: [Function, null]
    },
    trackId: {
        type: String
    },
})
const emit = defineEmits(['update:modelValue', 'update:label'])

const options = ref([]);
const isLoading = ref(false);


const handleSearch = debounce((query) => {
    if (!query.length) {
        options.value = []
        return;
    }
    isLoading.value = true
    window.axios.get(`${props.endpoint}?q=${query}`).then(({ data }) => {
        options.value = data.data;
        isLoading.value = false
    })
}, 200)

const emitInput = (optionId) => {
    emit('update:modelValue', optionId)
    emit('update:label', options.value.find(option => option.value == optionId)?.label)
}
</script>
