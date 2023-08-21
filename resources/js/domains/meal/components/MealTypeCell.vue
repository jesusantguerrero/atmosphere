<script setup lang="ts">
import { reactive } from "vue";

import LogerButtonTab from "@/Components/atoms/LogerButtonTab.vue";
import LogerApiSimpleSelect from "@/Components/organisms/LogerApiSimpleSelect.vue";

import IconClose from "@/Components/icons/IconClose.vue";
import IconHeart from "@/Components/icons/IconHeart.vue";
import IconHeartOutline from "@/Components/icons/IconHeartOutline.vue";

interface Dateable {
    name: string;
}

interface PlannedMeal {
    name: string;
    is_liked: boolean;
    dateable?: Dateable;
}

const props = defineProps<{
    plannedMeal?: PlannedMeal;
    mealType: Record<string, string>
}>();

const recipe = reactive<{
    id: string | null,
    name: string;
}>({
  id: null,
  name: "",
});

const emit = defineEmits(["submit"]);

const submit = () => {
  emit("submit", {...recipe, meal_type_id: props.mealType.id });
};

const emitCellEvent = (eventName: string) => {
    // @ts-ignore
    emit(eventName, props.plannedMeal)
}
</script>

<template>
  <div
    class="flex px-5 py-2 mb-4 overflow-visible border border-dashed rounded-md meal-cell border-primary w-ful"
    :class="[plannedMeal ? 'bg-base-lvl-2' : 'bg-base-lvl-3']"
  >
    <div class="flex justify-between w-full px-2 py-2 font-bold group bg-base-lvl-2 text-primary" v-if="plannedMeal">
        <span class="inline-block capitalize text-ellipsis">
            {{ plannedMeal.dateable?.name ?? plannedMeal.name }}
        </span>
        <div class="flex items-center space-x-2 text-xl transition">
            <AtButton class="hidden my-0 transition text-body-1/80 hover:text-error/80 group-hover:inline-block" @click="emitCellEvent('removed')">
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



<style lang="scss">
.meal-cell .multiselect__tags {
 border: none;
}
</style>
