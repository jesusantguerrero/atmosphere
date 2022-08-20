<template>
<NSelect
    filterable
    clearable
    remote
    size="large"
    :value="optionParser(modelValue)"
    :placeholder="placeholder"
    :options="options"
    :loading="isLoading"
    :clear-filter-after-select="false"
    @update:value="emitInput"
    @search="handleSearch"
>
    <template #action>
      <slot name="action" />
    </template>
</NSelect>
</template>


<script setup>
import { NSelect } from "naive-ui";
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
        type: String,
        default: "label"
    },
    trackId: {
        type: String
    },
    tag: {
        type: Boolean,
        default: true
    },
    once: {
        type: Boolean,
        default: true
    },
    resultParser: {
        type: [null, Function],
    }
})
const emit = defineEmits(['update:modelValue', 'update:label'])

const options = ref([]);
const isLoading = ref(false);

const optionParser = (option) => {
    const optionLabel = props.customLabel ? option[props.customLabel] : option.label;
    return optionLabel
}

const processResults = (apiOptions, query) => {
    let includeCustom = true;
    const originalMap = apiOptions.map(option => {
        const optionLabel = props.customLabel ? option[props.customLabel] : option.label;
        if (!props.tag || (includeCustom && optionLabel.includes(query))) includeCustom = false;

        const value = props.trackId ? option[props.trackId] : option.id || option
        return {
            label: typeof option !== 'string' ? optionLabel : option,
            value
        }
    })

    const custom = includeCustom ? [
        {
            label: query,
            value: 'new'
        }
    ]: [];

    return [...custom, ...originalMap]
}

const handleSearch = debounce((query) => {
    if (!query.length && !props.once) {
        options.value = []
        return;
    }

    if (props.once && options.value.length) {
        return
    }

    isLoading.value = true

    window.axios.get(`${props.endpoint}?q=${query}`).then(({ data }) => {
        options.value = processResults(data.data, query);
        isLoading.value = false
    })
}, 200)

const emitInput = (optionId) => {
    emit('update:modelValue', optionId)
    emit('update:label', options.value.find(option => option.value == optionId)?.label)
}
</script>
