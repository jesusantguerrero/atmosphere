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
                <header class="flex w-full justify-between items-center py-4 px-4">
                    <button @click="selectedGroup=null" v-if="selectedGroup">
                        <IconBack />
                    </button>
                    <h4 class="text-lg text-body-1 font-bold">Transaction Category</h4>
                    <div>
                        <LogerButtonTab icon="fa fa-cogs" />
                        <LogerButtonTab icon="fa fa-check"/>
                    </div>
                </header>
                <section class="w-full grid grid-cols-3 sm:grid-cols-4 gap-1 overflow-auto pb-10">
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

<script setup>
import { ref, computed, watch } from 'vue';
import LogerButtonTab from '../atoms/LogerButtonTab.vue';
import CategoryItem from './CategoryItem.vue';
import Modal from '../atoms/Modal.vue';
import IconBack from '../icons/IconBack.vue';

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
