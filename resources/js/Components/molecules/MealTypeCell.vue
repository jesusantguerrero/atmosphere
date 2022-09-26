<template>
  <div
    class="meal-cell border border-dashed border-primary rounded-md px-5 py-2 w-ful flex overflow-visible mb-4"
  >
    <div class="px-5 py-2 bg-base-lvl-2 font-bold text-primary" v-if="plannedMeal">
      {{ plannedMeal.dateable.name }}
    </div>
    <div class="flex w-full overflow-visible" v-else>
      <LogerApiSimpleSelect
        v-model="recipe.id"
        v-model:label="recipe.name"
        class="w-full"
        tag
        custom-label="name"
        track-id="id"
        :bordered="false"
        :placeholder="`Add ${mealType.name}`"
        endpoint="/api/recipes"
      />
      <LogerTabButton @click="submit" v-if="recipe.id">Save</LogerTabButton>
    </div>
  </div>
</template>

<script setup>
import { reactive } from "vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import LogerApiSimpleSelect from "../organisms/LogerApiSimpleSelect.vue";

const props = defineProps({
  plannedMeal: {
    type: [Object, null],
  },
  mealType: {
    type: Object,
    required: true,
  },
});

const recipe = reactive({
  id: "",
  name: "",
});

const emit = defineEmits(["submit"]);

const submit = (name, value) => {
  emit("submit", {...recipe, meal_type_id: props.mealType.id });
};
</script>

<style lang="scss">
.meal-cell .multiselect__tags {
 border: none;
}
</style>
