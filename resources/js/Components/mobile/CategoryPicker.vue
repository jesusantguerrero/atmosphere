<template>
    <section>
        <header class="px-4">
            <CategoryItem
                :label="pickerPlaceholder.label"
                @click="isOpen=!isOpen"
            />
        </header>
        <article v-if="isOpen">
            <header class="flex w-full justify-between items-center">
                <h4 class="text-lg text-body-1 font-bold">Transaction Category</h4>
                <div>
                    <LogerTabButton icon="fa fa-cogs" />
                    <LogerTabButton icon="fa fa-check"/>
                </div>
            </header>
            <section class="w-full grid grid-cols-4 gap-2 h-56 overflow-auto ic-scroller">
                <template v-if="selectedGroup">
                    <button @click="selectedGroup=null">
                        Back
                    </button>
                    <CategoryItem
                        v-for="category in selectedGroup[childrenKey]"
                        :label="category.label"
                        :color="category.color"
                        :icon="category.icon"
                        @click="setCategory(category)"
                    />
                </template>
                <template v-else>
                    <CategoryItem
                        v-for="category in options"
                        :label="category.label"
                        :color="category.color"
                        :icon="category.icon"
                        @click="selectedGroup=category"
                    />
                </template>
            </section>
        </article>
    </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import LogerTabButton from '../atoms/LogerTabButton.vue';
import CategoryItem from './CategoryItem.vue';

const props = defineProps({
    options: {
        type: Array,
    },
    childrenKey: {
        type: String,
        default: 'children'
    },
    placeholder: {
        type: String,
        default: 'Choose Category'
    },
    isPickerOpen: {
        type: Boolean
    }
})

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const selectedGroup = ref();
const selectedCategory = ref();

watch(isOpen,
() => {
    emit('update:isPickerOpen', isOpen.value)
})

const setCategory = (category) => {
    emit('update:modelValue', category.value)
    selectedCategory.value = category;
    isOpen.value = false;
    selectedGroup.value = null;
}

const pickerPlaceholder = computed(() => {
    return selectedCategory.value ?? {
        label: props.placeholder,
        icon: '',
    }
})
</script>
