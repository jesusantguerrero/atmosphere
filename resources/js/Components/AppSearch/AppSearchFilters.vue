<script setup>
import Multiselect from "vue-multiselect";

defineProps({
  title: {
    type: String,
    default: "Filter by",
  },
  filters: {
    type: Object,
  },
  fields: {
    type: Array,
    default: () => {
      return [];
    },
  },
  isActive: {
    type: Boolean,
  },
});

defineEmits(["open", "close", "select"]);
</script>

<template>
  <section class="h-full">
    <template v-if="isActive">
      <span class="w-48 px-2 font-semibold h-full flex items-center">
        {{ title }}
      </span>
      <slot v-for="field in fields" :key="field" :name="field">
        <Multiselect
          :modelValue="filters[field].value"
          :showLabels="false"
          @update:modelValue="$emit('select', field, $event)"
          placeholder="Select Year"
          :options="filters[field].options"
        />
      </slot>
    </template>
    <button v-else class="hover:bg-gray-50 px-2 h-full" @click="$emit('open')">
      <slot name="icon">
        <IMdiFilter />
      </slot>
    </button>
    <button
      title="Reset all filters"
      class="hover:bg-red-400 bg-gray-100 h-6 transition-all flex items-center justify-center w-6 my-auto hover:text-white px-2 rounded-full"
      @click="$emit('close')"
      v-if="isActive"
    >
      <IMdiClose />
    </button>
  </section>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style>
.multiselect__option--highlight {
  @apply bg-primary;
}

.multiselect__tags,
.multiselect__single,
.multiselect__input {
  @apply bg-base-lvl-2;
}
</style>
