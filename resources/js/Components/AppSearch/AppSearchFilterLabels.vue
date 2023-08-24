<script lang="ts" setup>
import { computed } from "vue";
import IconClose from "@/components/icons/IconClose.vue";

const props = defineProps({
  filters: {
    type: Object,
  },
  sorts: {
    type: Object,
  },
});

const emit = defineEmits(["update:sorts", "update:filters"]);

const removeSort = (field) => {
  const sortField = props.sorts[field];
  emit("update:sorts", {
    ...props.sorts,
    [field]: {
      ...sortField,
      value: false,
      direction: "",
    },
  });
};

const removeFilter = (name) => {
  emit("update:filters", {
    ...props.filters,
    [name]: {
      ...props.filters[name],
      value: "",
    },
  });
};

const getAppliedValues = (paramConfig) => {
  return Object.entries(paramConfig).reduce((applied, [name, param]) => {
    if (param.value) {
      applied.push({ ...param, name });
    }
    return applied;
  }, []);
};

const appliedFilters = computed(() => {
  return getAppliedValues(props.filters);
});

const appliedSorts = computed(() => {
  return getAppliedValues(props.sorts);
});
</script>

<template>
  <div class="flex space-x-4">
    <article v-if="appliedSorts.length">
      <section v-if="appliedSorts" class="flex items-center">
        <span class="font-bold">Sorted by</span>
        <div
          v-for="sort in appliedSorts"
          :key="sort.label"
          class="ml-2 bg-red-200 text-gray-600 rounded-md px-2 flex items-center font-bold py-1"
        >
          <span> {{ sort.label }} {{ sort.direction }} </span>
          <IconClose
            class="ml-2 cursor-pointer"
            @click="removeSort(sort.name)"
          />
        </div>
      </section>
    </article>
    <article v-if="appliedFilters.length">
      <section v-if="appliedFilters" class="flex items-center">
        <span class="font-bold">Filtered by</span>
        <p
          v-for="filter in appliedFilters"
          :key="filter.label"
          class="ml-2 bg-red-200 rounded-md px-2 text-gray-600 py-1 flex items-center"
        >
          {{ filter.label }}:
          <span class="font-bold ml-2 capitalize">{{ filter.value }}</span>
          <IconClose
            class="ml-2 cursor-pointer"
            @click="removeFilter(filter.name)"
          />
        </p>
      </section>
    </article>
  </div>
</template>
