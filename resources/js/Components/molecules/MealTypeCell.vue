<template>
  <div
    class="border border-dashed bg-base-lvl-2 rounded-md px-5 py-2 w-ful flex overflow-hidden mb-4"
  >
    <div class="px-5 py-2 bg-base-lvl-2 font-bold text-primary" v-if="plannedMeal">
      {{ plannedMeal.dateable.name }}
    </div>
    <div class="flex" v-else>
      <LogerApiSelect
        v-model="recipe.id"
        v-model:label="recipe.name"
        class="w-full"
        tag
        custom-label="name"
        track-id="id"
        :placeholder="`Add ${mealType.name}`"
        endpoint="/api/recipes"
      />
      <LogerTabButton @click="submit">Save</LogerTabButton>
    </div>
  </div>
</template>

<script setup>
import { reactive } from "vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

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
