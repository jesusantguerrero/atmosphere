<template>
<div class="border border-dashed bg-base-lvl-2 rounded-md px-5 py-2 w-ful flex overflow-hidden mb-4">
        <div class="px-5 py-2 bg-base-lvl-2 font-bold text-primary-400" v-if="plannedMeal">
            {{ plannedMeal.dateable.name }}
        </div>
        <div class="flex" v-else>
            <LogerApiSelect
                :model-value="modelValue.id"
                :label="modelValue.name"
                @update:modelValue="updateValue('id', $event)"
                @update:label="updateValue('name', $event)"
                class="w-full"
                tag
                custom-label="name"
                track-id="id"
                :placeholder="`Add ${ mealType.name }`"
                endpoint="/api/recipes"
            />
            <LogerTabButton  @click="emit('action')">Save</LogerTabButton>
        </div>
    </div>
</template>

<script setup>
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import LogerApiSelect from "@/Components/organisms/LogerApiSelect.vue";

const props = defineProps({
    plannedMeal: {
        type: [Object, null]  
    },
    mealType: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Object, 
        required: true
    },
})

const emit = defineEmits(['update:modelValue', 'action'])

const updateValue = (name, value) => {
    emit('update:modelValue', {...props.modelValue, [name]: value})
}
</script>