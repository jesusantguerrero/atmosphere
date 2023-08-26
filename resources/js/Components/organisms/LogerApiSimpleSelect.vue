
<script setup lang="ts">
import { NSelect } from "naive-ui";
import { ref, onMounted } from "vue";
import { debounce } from "lodash";
import { RenderLabel } from "naive-ui/es/_internal/select-menu/src/interface";
import { watch } from "vue";

const props = withDefaults(defineProps<{
    modelValue: string | null;
    endpoint: string;
    placeholder?: string;
    allowCreate?: boolean;
    customLabel: string;
    renderLabel?: RenderLabel;
    trackId: string;
    label: string;
}>(), {
    customLabel: "label",
})

const emit = defineEmits(['update:modelValue', 'update:label', 'update:value'])

const options = ref<{
    label: string;
    value: string;
}[]>([]);
const isLoading = ref(false);


const optionParser = (option: string | Record<string, string>) => {
    if (!option) return option

    if (typeof option == 'string') {
        return option;
    }
    return props.customLabel && option ? option[props.customLabel] : option.label;
}

const resultParser = (apiOptions: Record<string, string>[], query: string = "") => {
    let includeCustom = true;
    const originalMap = apiOptions.map(option => {
        const optionLabel = props.customLabel ? option[props.customLabel] : option.label;
        if (includeCustom && query && optionLabel.toLowerCase().includes(query)) includeCustom = false;

        return {
            label: optionLabel,
            value: props.trackId ? option[props.trackId] : option.id
        }
    })

    const custom = includeCustom ? [
        {
            label: query,
            value: `new::${query}`
        }
    ]: [];

    return [...custom, ...originalMap]
}

const handleSearch = debounce((query) => {
    if (!query.length) {
        options.value = []
        return;
    }
    isLoading.value = true
    window.axios.get(`${props.endpoint}?search=${query}`).then(({ data }) => {
        options.value = resultParser(data?.data || data, query.toLowerCase());
        isLoading.value = false
    })
}, 200)

const emitInput = (optionId: string, option?: Record<string, string>) => {
    const optionData = option ?? options.value.find(option => option.value == optionId)
    emit('update:modelValue', option ?? optionId)
    emit('update:value', optionData)
    emit('update:label', optionData?.label)
}

const fetchInitialValue = (id: string) => {
    window.axios.get(`${props.endpoint}/${id}`).then(({ data }) => {
        options.value = resultParser([data?.data || data])
        .filter( value => value.label );
        emitInput(id, data)
        isLoading.value = false
    })
}

watch(() => props.modelValue, (value, oldValue) => {
    if (value !== oldValue && typeof value == 'string') {
        fetchInitialValue(value)
    }
})

onMounted(() => {
    if (props.modelValue) {
       fetchInitialValue(props.modelValue)
    }
})
</script>

<template>
<NSelect
    :value="optionParser(modelValue)"
    filterable
    clearable
    remote
    size="large"
    :placeholder="placeholder"
    :options="options"
    :loading="isLoading"
    v-bind="$attrs"
    :render-label="renderLabel"
    :clear-filter-after-select="false"
    @update:value="emitInput"
    @search="handleSearch"
>
    <template #action>
      <slot name="action" />
    </template>
</NSelect>
</template>
