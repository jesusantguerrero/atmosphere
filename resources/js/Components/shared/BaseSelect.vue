<script setup lang="ts">
import axios from "axios";
import { debounce } from "lodash";
import { computed, ref, watch } from "vue";

type SelectOption = Object | any[] | string | number | null;

interface Props {
  modelValue: SelectOption;
  trackBy: string;
  multiple?: boolean;
  label: string;
  id?: string | number;
  options?: any[];
  disabled?: boolean;
  placeholder?: string;
  hideSelected?: boolean;
  showLabels?: boolean;
  endpoint?: string;
  tag?: boolean;
  allowCreate?: boolean;
  customLabel?: Function;
  size: "sm" | "md" | "lg";
}

const props = withDefaults(defineProps<Props>(), {
  trackBy: "value",
  label: "label",
  placeholder: "Type and select option…",
  hideSelected: false,
  showLabels: false,
  size: "sm",
});

const emit = defineEmits([
  "update:modelValue",
  "input",
  "select",
  "remove",
  "searchChange",
  "open",
  "close",
  "update:value",
  "update:label",
]);

const localOptions = ref(props.options ?? []);
const isLoading = ref(false);
const selected = ref();

watch(() => props.options, (options: []) => {
    localOptions.value = options ?? []
}, { immediate: true })

const optionParser = (option: string | Record<string, string>) => {
  if (!option) return option;

  if (typeof option == "string") {
    return option;
  }
  return props.label && option ? option[props.label] : option.label;
};

const selectedText = computed(() => {
  return optionParser(selected.value ?? props.modelValue);
});

const resultParser = (apiOptions: Record<string, string>[], query: string = "") => {
  let includeCustom = props.allowCreate;
  const originalMap = apiOptions.map((option) => {
    const optionLabel = props.label ? option[props.label] : option.label;
    if (includeCustom && query && optionLabel.toLowerCase().includes(query))
      includeCustom = false;

    return {
      ...option,
      [props.label]: optionLabel,
      [props.trackBy]: props.trackBy ? option[props.trackBy] : option.id,
    };
  });

  const custom = includeCustom
    ? [
        {
          [props.label]: query,
          [props.trackBy]: `new::${query}`,
        },
      ]
    : [];

  return [...custom, ...originalMap];
};

const setLocalOptions = (data: any[]) => {
  localOptions.value = data;
};

const handleSearch = debounce((query) => {
  if (!query.length) {
    localOptions.value = [];
    return;
  }
  isLoading.value = true;
  const params = props.endpoint?.includes("?") ? `&search=${query}` : `?search=${query}`;

  axios
    .get(`${props.endpoint}${params}`)
    .then(({ data }) => {
      setLocalOptions(resultParser(Array.isArray(data) ? data : data.data, query));
    })
    .finally(() => {
      isLoading.value = false;
    });
}, 400);

const emitInput = (optionId: string | number, option?: Record<string, string>) => {
  const optionData =
    option ?? localOptions.value.find((option) => option[props.trackBy] == optionId);
  selected.value = optionData;
  emit("update:modelValue", optionId);
  emit("update:value", optionData);
  emit("update:label", optionData[props.label]);
};

const multiselectListeners = {
  "update:modelValue": (value: SelectOption) => emit("update:modelValue", value),
  select: (selectedOption: string) => emit("select", selectedOption),
  remove: (removedOption: string) => emit("remove", removedOption),
  close: (value: string) => emit("close", value),
  open: (id: string) => {
    emit("open", id);
    if (props.endpoint) {
      handleSearch(" ");
    }
  },
  searchChange: (searchQuery: string) => {
    if (props.endpoint) {
      handleSearch(searchQuery);
    } else {
      emit("searchChange", searchQuery);
    }
  },
  value: (optionId: string, option?: Record<string, string>) => {
    emitInput(optionId, option);
  },
};

const sizes = {
  sm: "text-sm h-9",
  md: "h-14",
  lg: "h-16",
};

const classes = computed(() => {
  const sizeClasses = sizes[props.size];

  return [sizeClasses].join(" ");
});

const fetchInitialValue = (id: string | number) => {
  if (localOptions.value.find((option) => option.value == id)) {
    return;
  }
  let url = new URL(`${props.endpoint}`, location.origin);
  const finalUrl = `${url.pathname}/${id}${url.search}`;
  axios.get(finalUrl).then(({ data }) => {
    setLocalOptions(resultParser(Array.isArray(data?.data) ? data.data : [data]));

    emitInput(id, Array.isArray(data?.data) ? null : data.data);
    isLoading.value = false;
  });
};

watch(
  () => props.modelValue,
  (value, oldValue) => {
    if (value !== oldValue && typeof value == "string") {
      fetchInitialValue(value);
    } else {
      selected.value = props.modelValue;
    }
  },
  {
    immediate: true,
  }
);
</script>

<template>
  <multiselect
    class="base-select"
    :class="[classes, size]"
    :id="id"
    :modelValue="selected"
    :disabled="disabled"
    :trackBy="trackBy"
    :loading="isLoading"
    :label="label"
    :tag="tag"
    :multiple="multiple"
    :internal-search="!endpoint"
    :placeholder="placeholder"
    :hideSelected="hideSelected"
    :showLabels="showLabels"
    :allowCreate="allowCreate"
    :customLabel="customLabel"
    :options="localOptions"
    :size="size"
    v-on="multiselectListeners"
  >
    <template v-slot:singleLabel="{ option }">
      <slot name="singleLabel" :option="option" />
    </template>
    <template v-slot:option="{ option }">
      <slot name="option" :option="option" />
    </template>
  </multiselect>
</template>

<style lang="scss">
@import "vue-multiselect/dist/vue-multiselect.css";
</style>

<style lang="scss">
.multiselect__option--highlight {
  @apply bg-primary;
}

.multiselect__tags,
.multiselect__single,
.multiselect__input {
  @apply bg-base-lvl-2;
  margin-bottom: 0 !important;
}

.multiselect__single {
  display: inline-flex;
}

.multiselect__tags {
  padding-top: 0 !important;
  display: flex !important;
  align-items: center;
}

.multiselect__content-wrapper {
  &::-webkit-scrollbar-thumb {
    background-color: transparentize($color: #000000, $amount: 0.8);
    border-radius: 4px;

    &:hover {
      background-color: transparentize($color: #000000, $amount: 0.8);
    }
  }

  &::-webkit-scrollbar {
    background-color: transparent;
    width: 8px;
    height: 10px;
  }
}

.multiselect {
  &__placeholder {
    @apply inline-block my-auto first-letter:capitalize;
  }

  &__input {
    &::placeholder {
      @apply capitalize;
    }
  }

  &.sm {
    @apply h-9;
    .multiselect__select,
    .multiselect__tags {
      @apply h-9;
    }
  }

  &.md {
    @apply h-14;
    .multiselect__select,
    .multiselect__tags {
      @apply h-14;
    }
  }

  &.lg {
    @apply h-16;
    .multiselect__select,
    .multiselect__tags {
      @apply h-16;
    }
  }
}
</style>
