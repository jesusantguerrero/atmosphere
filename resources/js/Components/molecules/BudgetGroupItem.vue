<template>
<article>
    <header class="flex justify-between py-2 px-4">
        <div class="flex items-center space-x-2">
            <div class="cursor-grab">
                <IconDrag class="handle" />
            </div>
            <LogerTabButton @click="isExpanded=!isExpanded">
                <i class="fa" :class="toggleIcon" />
            </LogerTabButton>
            <div>
                <h4> {{ item.name }}</h4>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <p>
                {{ formatMoney(item.spent) }} of
                {{ formatMoney(item.budgeted) }}  
            </p>
            <LogerTabButton @click="toggleAdding">
                <i class="fa fa-plus" />
            </LogerTabButton>
            <LogerTabButton> <i class="fa fa-ellipsis-v"></i></LogerTabButton>
        </div>
    </header>
    <section class="pl-4 border-l-4 border-primary-500 bg-base-700">
            <slot :isExpanded="isExpanded" :toggleAdding="toggleAdding" :isAdding="isAdding" name="content"/>
    </section>
</article>
</template>

<script setup>

import IconDrag from "../icons/IconDrag.vue";
import { computed, ref }  from "vue";
import LogerTabButton from "@/Components/atoms/LogerTabButton.vue";
import formatMoney from "@/utils/formatMoney";

defineProps({
    item: {
        type: Object,
        required: true
    }
})

const isExpanded = ref(false);
const toggleIcon = computed(() => isExpanded.value ? 'fa-chevron-up' : 'fa-chevron-down')
const isAdding = ref(false);
const toggleAdding = () => isAdding.value = !isAdding.value;
</script>