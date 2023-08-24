<script setup lang="ts">
// @ts-ignore
import { AtInput } from "atmosphere-ui";
import { ref } from "vue";
import { useDebounceFn } from "@vueuse/shared";
import AppSearchFilters from "./AppSearchFilters.vue";

const props = defineProps({
  filters: {
    type: Object,
  },
  sorts: {
    type: Object,
  },
  modelValue: {
    type: String,
  },
  placeholder: {
    type: String,
    default: "Buscar...",
  },
  hasFilters: {
    type: Boolean,
  },
});

const emit = defineEmits([
  "update:sorts",
  "update:filters",
  "update:modelValue",
  "search",
  "clear",
  "blur",
  "focus",
]);

const sort = (field) => {
  const sortField = props.sorts[field];
  const direction = ["desc", ""].includes(sortField.direction) ? `asc` : "desc";
  emit("update:sorts", {
    ...props.sorts,
    [field]: {
      ...sortField,
      value: true,
      direction,
    },
  });
  visibleOption.value = "";
};

const filter = (name, value) => {
  emit("update:filters", {
    ...props.filters,
    [name]: {
      ...props.filters[name],
      value,
    },
  });
  visibleOption.value = "";
};

const visibleOption = ref("");
const isVisibleOption = (optionName) => {
  return optionName == visibleOption.value;
};

const resetFilters = () => {
  emit("clear");
  visibleOption.value = "";
};

const handleInput = useDebounceFn((searchText) => {
  emit("update:modelValue", searchText);
}, 200);
</script>

<template>
  <div
    class="flex rounded-md overflow-hidden bg-base-lvl-3 w-full h-12 border border-base-lvl-1"
  >
    <AtInput
      v-if="!visibleOption"
      class="rounded-md bg-base-lvl-3 w-full h-12 border-none"
      :is-borderless="true"
      borderless
      input-class="overflow-visible"
      :placeholder="placeholder"
      :model-value="modelValue"
      @update:modelValue="handleInput"
      @focus="$emit('focus')"
      @blur="$emit('blur')"
      @keydown.enter.stop="$emit('search')"
    >
      <template #prefix>
        <button
          class="rounded-l-md px-2 hover:bg-gray-50 md:px-4"
          @click.stop="$emit('search')"
        >
          <IMdiSearch />
        </button>
      </template>
      <template #suffix>
        <button
          title="Reset all filters"
          class="hover:bg-red-400 bg-gray-100 h-6 transition-all mr-4 flex items-center justify-center w-6 my-auto hover:text-white px-2 rounded-full"
          @click="resetFilters()"
          v-if="hasFilters"
        >
          <IMdiClose />
        </button>
      </template>
    </AtInput>
    <AppSearchFilters
      v-if="!isVisibleOption('filter') && sorts"
      class="flex space-x-4 items-center"
      title="Sort by"
      :is-active="isVisibleOption('sort')"
      :filters="sorts"
      :fields="['title', 'releaseYear']"
      @open="visibleOption = 'sort'"
      @close="visibleOption = null"
    >
      <template #releaseYear>
        <button
          class="hover:bg-gray-50 px-2 flex h-full items-center"
          @click="sort('releaseYear')"
        >
          <IMdiSort /> Year
        </button>
      </template>

      <template #title>
        <button class="hover:bg-gray-50 px-2 flex items-center" @click="sort('title')">
          <IMdiSort /> Name
        </button>
      </template>

      <template #icon>
        <IMdiSort />
      </template>
    </AppSearchFilters>
    <AppSearchFilters
      class="flex space-x-4 items-center text-body-1"
      v-if="!isVisibleOption('sort') && filters"
      :is-active="isVisibleOption('filter')"
      :fields="['releaseYear', 'programType']"
      :filters="filters"
      @open="visibleOption = 'filter'"
      @close="visibleOption = null"
      @select="filter"
    />
  </div>
</template>
