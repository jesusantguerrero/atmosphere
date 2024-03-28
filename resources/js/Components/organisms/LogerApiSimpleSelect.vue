<script setup lang="ts">
import { NSelect, NTooltip, SelectGroupOption } from "naive-ui";
import { ref, onMounted, watch, computed } from "vue";
import { debounce, uniqBy } from "lodash";
import { RenderLabel } from "naive-ui/es/_internal/select-menu/src/interface";
import axios from "axios";
import { h } from "vue";
import { VNode } from "vue";
import { VNodeChild } from "vue";

const props = withDefaults(
  defineProps<{
    modelValue: string | number | null;
    endpoint: string;
    placeholder?: string;
    allowCreate?: boolean;
    customLabel: string;
    renderLabel?: RenderLabel;
    renderOption: (info: {
      node: VNode;
      option: SelectOption | SelectGroupOption;
      selected: boolean;
    }) => VNodeChild;
    trackId: string;
    label?: string;
  }>(),
  {
    customLabel: "label",
    renderLabel: (option: SelectOption, isSelected) => {
      if (option.value?.includes?.("new::") && !isSelected) {
        return [
          h(
            "strong",
            {
              class: "font-bold text-secondary",
            },
            "Create: "
          ),
          option.label as string,
        ];
      }
      return option.label;
    },
    renderOption: ({ node, option, selected }: any) =>
      h(NTooltip, null, {
        trigger: () => node,
        default: () => option.label,
      }),
  }
);

const emit = defineEmits(["update:modelValue", "update:label", "update:value"]);
interface SelectOption {
  label: string;
  value: string;
}

const options = ref<SelectOption[]>([]);
const isLoading = ref(false);

const optionParser = (option: string | Record<string, string>) => {
  if (!option) return option;

  if (typeof option == "string") {
    return option;
  }
  return props.customLabel && option ? option[props.customLabel] : option.label;
};
const selected = ref();

const selectedText = computed(() => {
  return optionParser(selected.value ?? props.modelValue);
});

const getOptionFromLabel = (option: string): SelectOption => {
  return {
    label: option,
    value: `new::${option}`,
  };
};

const resultParser = (apiOptions: Record<string, string>[], query: string = "") => {
  let includeCustom = props.allowCreate;
  const queryTag = query.toLowerCase();
  const originalMap = apiOptions.map((option) => {
    const optionLabel = props.customLabel ? option[props.customLabel] : option.label;
    if (includeCustom && query && optionLabel.toLowerCase().includes(queryTag))
      includeCustom = false;

    return {
      label: optionLabel,
      value: props.trackId ? option[props.trackId] : option.id,
    };
  });

  const custom = includeCustom ? [getOptionFromLabel(query)] : [];

  return [...custom, ...originalMap];
};

const handleSearch = debounce((query) => {
  if (!query.length) {
    setOptions([]);
    return;
  }

  isLoading.value = true;

  axios.get(`${props.endpoint}?search=${query}`).then(({ data }) => {
    setOptions(resultParser(data?.data || data, query));
    isLoading.value = false;
  });
}, 200);

const emitInput = (optionId: string | number, option?: Record<string, string>) => {
  let optionData = option ?? options.value.find((option) => option.value == optionId);
  if (optionData && optionId && typeof optionId == "string") {
    optionData = getOptionFromLabel(optionId);
  }
  selected.value = optionData;

  emit("update:modelValue", optionId);
  emit("update:value", optionData);
  emit("update:label", optionData?.label);
};

const hasNew = (option: SelectOption) => option.value?.includes?.("new::");
const setOptions = (newOptions: SelectOption[]) => {
  let currentOptions = options.value.filter(hasNew);
  if (newOptions.some(hasNew) && options.value.some(hasNew)) {
    currentOptions = currentOptions.filter((option) => !hasNew(option));
  }
  options.value = uniqBy([...currentOptions, ...newOptions], "value");
};

const fetchInitialValue = (id: string | number) => {
  if (options.value.find((option) => option.value == id)) {
    return;
  }
  axios.get(`${props.endpoint}/${id}`).then(({ data }) => {
    setOptions(resultParser([data?.data || data]).filter((value) => value.label));

    emitInput(id, Array.isArray(data) ? null : data);
    isLoading.value = false;
  });
};

watch(
  () => props.modelValue,
  (value, oldValue) => {
    if (value !== oldValue && typeof value == "string") {
      fetchInitialValue(value);
    }
  }
);

onMounted(() => {
  if (props.modelValue) {
    fetchInitialValue(props.modelValue);
  }
});
</script>

<template>
  <NSelect
    :value="selectedText"
    filterable
    clearable
    remote
    :tag="allowCreate"
    size="large"
    :placeholder="placeholder"
    :options="options"
    :loading="isLoading"
    v-bind="$attrs"
    :render-label="renderLabel"
    :render-option="renderOption"
    :clear-filter-after-select="false"
    @update:value="emitInput"
    @search="handleSearch"
  >
    <template #action>
      <slot name="action" />
    </template>
    <template #arrow>
      <slot name="arrow" />
    </template>
    <template #empty>
      <slot name="empty" />
    </template>
  </NSelect>
</template>
