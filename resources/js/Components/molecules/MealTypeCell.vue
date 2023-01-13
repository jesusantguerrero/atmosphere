<template>
  <div
    class="meal-cell border border-dashed border-primary rounded-md px-5 py-2 w-ful flex overflow-visible mb-4"
    :class="[plannedMeal ? 'bg-base-lvl-2' : 'bg-base-lvl-3']"
  >
    <div class="px-2 py-2 group bg-base-lvl-2 font-bold justify-between text-primary w-full flex" v-if="plannedMeal">
        <span class="capitalize text-ellipsis inline-block">
            {{ plannedMeal.dateable.name }}
        </span>
        <div class="transition flex space-x-2 items-center text-xl">
            <AtButton class="hidden transition my-0 text-body-1/80 hover:text-error/80 group-hover:inline-block" @click="emitCellEvent('removed')">
                <IconClose />
            </AtButton>
            <AtButton class="group-hover:text-red-500" @click="emitCellEvent('toggle-like')">
                <IconHeart v-if="plannedMeal.is_liked" />
                <IconHeartOutline v-else />
            </AtButton>
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
      <LogerButtonTab @click="submit" v-if="recipe.id">Save</LogerButtonTab>
    </div>
  </div>
</template>

<script setup>
import { reactive } from "vue";
import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import LogerApiSimpleSelect from "../organisms/LogerApiSimpleSelect.vue";
import IconClose from "../icons/IconClose.vue";
import IconHeart from "../icons/IconHeart.vue";
import IconHeartOutline from "../icons/IconHeartOutline.vue";
import LogerButton from "../atoms/LogerButton.vue";

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
  id: null,
  name: "",
});

const emit = defineEmits(["submit"]);

const submit = (name, value) => {
  emit("submit", {...recipe, meal_type_id: props.mealType.id });
};

const emitCellEvent = (eventName) => {
    emit(eventName, props.plannedMeal)
}
</script>

<style lang="scss">
.meal-cell .multiselect__tags {
 border: none;
}
</style>
