<script setup lang="ts">
import { ref } from "vue";
import { debounce } from "lodash";

const props = defineProps<{
    modelValue: Object,
    endpoint: string,
    placeholder: string
    allowCreate: boolean
    customLabel: Function | null
    trackId: string
}>()

const emit = defineEmits(['update:modelValue', 'update:label'])

const options = ref([]);
const isLoading = ref(false);


const handleSearch = debounce((query) => {
    if (!query.length) {
        options.value = []
        return;
    }
    isLoading.value = true
    axios.get(`${props.endpoint}?q=${query}`).then(({ data }) => {
        options.value = data.data ?? data;
        isLoading.value = false
    })
}, 200)
</script>

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

