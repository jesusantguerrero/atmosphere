
<script setup lang="ts">
import { ref, computed, watch } from 'vue';

import LogerButtonTab from '@/Components/atoms/LogerButtonTab.vue';
import IconBack from '@/Components/icons/IconBack.vue';
import Modal from '@/Components/atoms/Modal.vue';

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
        iconClass: 'fa fa-tags'
    }
})
</script>

<template>
    <section>
        <header>
            <CategoryItem
                :label="pickerPlaceholder.label"
                :icon-class="pickerPlaceholder.iconClass"
                :color="pickerPlaceholder.color"
                @click="isOpen=!isOpen"
            />
        </header>
        <Modal
            :show="isOpen"
            max-width="mobile"
            :closeable="true"
            @close="isOpen=false"
        >
            <article >
                <header class="flex items-center justify-between w-full px-4 py-4">
                    <button @click="selectedGroup=null" v-if="selectedGroup">
                        <IconBack />
                    </button>
                    <h4 class="text-lg font-bold text-body-1">Transaction Category</h4>
                    <div>
                        <LogerButtonTab icon="fa fa-cogs" />
                        <LogerButtonTab icon="fa fa-check"/>
                    </div>
                </header>
                <section class="grid w-full grid-cols-3 gap-1 pb-10 overflow-auto sm:grid-cols-4">
                    <template v-if="selectedGroup">
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
        </Modal>
    </section>
</template>

