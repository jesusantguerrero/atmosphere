<template>
  <div
    class="meal-cell border border-dashed border-primary rounded-md px-5 py-2 w-ful flex overflow-visible mb-4"
    :class="[plannedMeal ? 'bg-base-lvl-2' : 'bg-base-lvl-3']"
  >
    <div class="px-5 py-2 group bg-base-lvl-2 font-bold justify-between text-primary w-full  flex" v-if="plannedMeal">
        <span>
            {{ plannedMeal.dateable.name }}
        </span>
        <div class="transition flex space-x-2">
            <AtButton class="hidden transition group-hover:inline-block" @click="onLiked">Remove</AtButton>
            <AtButton class="group-hover:text-red-500" @click="onLiked">Like</AtButton>
        </div>
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

const onLiked = () => {
    emit('toggle-like', props.plannedMeal)
}
</script>

<style lang="scss">
.meal-cell .multiselect__tags {
 border: none;
}
</style>
