<script setup lang="ts">
import { AtFieldCheck } from "atmosphere-ui";
import { reactive } from "vue";
import { NSelect } from "naive-ui";

const props = defineProps({
    label: {
        type: String
    },
    options: {
        type: Array,
        default() {
            return []
        }
    },
    multiple: {
        type: Boolean,
        default: false
    },
    modelValue: {
        type: [Object, Array, String, null],
        default: null
    }
})

const state = reactive({
    isActive: !!props.modelValue?.length
});
</script>

<template>
    <article class="rounded-md py-2">
        <header class="flex justify-between">
            <AtFieldCheck
                v-model="state.isActive"
                :label="label"
                class="w-full rounded-md py-4 inline-block"
            />
        </header>

        <section v-if="state.isActive" class="px-4 mt-4 space-y-2">
            <NSelect
                filterable
                clearable
                size="large"
                :multiple="multiple"
                :value="modelValue"
                @update:value="$emit('update:modelValue', $event)"
                :default-expand-all="true"
                :options="options"
            />
        </section>
    </article>
</template>


